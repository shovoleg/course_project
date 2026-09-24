<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\SocialAccountLinker;
use League\OAuth2\Client\Provider\Github;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class GitHubAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private SocialAccountLinker $linker,
        private UrlGeneratorInterface $urls,
        #[Autowire('%env(GITHUB_CLIENT_ID)%')]
        private string $clientId,
        #[Autowire('%env(GITHUB_CLIENT_SECRET)%')]
        private string $clientSecret,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oauth_github_check';
    }

    public function authenticate(Request $request): Passport
    {
        if (!$this->isConfigured() || !$request->query->get('code')) {
            throw new CustomUserMessageAuthenticationException('auth.oauth_failed');
        }
        $saved = (string) $request->getSession()->get('oauth_github_state');
        if ($saved === '' || !hash_equals($saved, (string) $request->query->get('state'))) {
            throw new CustomUserMessageAuthenticationException('auth.oauth_failed');
        }
        $provider = $this->provider();
        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => (string) $request->query->get('code')]);
            $owner = $provider->getResourceOwner($token);
            $email = trim((string) $owner->getEmail());
            if ($email === '') {
                $email = $this->primaryEmail($provider, $token);
            }
        } catch (CustomUserMessageAuthenticationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('auth.oauth_failed');
        }
        if ($email === '') {
            throw new CustomUserMessageAuthenticationException('auth.no_email');
        }
        $user = $this->linker->link('github', (string) $owner->getId(), $email);

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), static fn () => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urls->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urls->generate('app_login'));
    }

    public function authorizationUrl(Request $request): string
    {
        $provider = $this->provider();
        $url = $provider->getAuthorizationUrl();
        $request->getSession()->set('oauth_github_state', $provider->getState());

        return $url;
    }

    private function provider(): Github
    {
        return new Github([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri' => $this->urls->generate('oauth_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    private function primaryEmail(Github $provider, mixed $token): string
    {
        $request = $provider->getAuthenticatedRequest('GET', 'https://api.github.com/user/emails', $token);
        $emails = $provider->getParsedResponse($request);
        if (!is_array($emails)) {
            return '';
        }
        foreach ($emails as $row) {
            if (is_array($row) && !empty($row['primary']) && !empty($row['verified']) && !empty($row['email'])) {
                return (string) $row['email'];
            }
        }
        foreach ($emails as $row) {
            if (is_array($row) && !empty($row['email'])) {
                return (string) $row['email'];
            }
        }

        return '';
    }
}
