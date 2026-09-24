<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\GitHubAuthenticator;
use App\Security\GoogleAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'oauth_google_start')]
    public function google(Request $request, GoogleAuthenticator $google): Response
    {
        if (!$google->isConfigured()) {
            $this->addFlash('warning', 'auth.oauth_unconfigured');

            return $this->redirectToRoute('app_login');
        }

        return $this->redirect($google->authorizationUrl($request));
    }

    #[Route('/connect/google/check', name: 'oauth_google_check')]
    public function googleCheck(): void
    {
        throw new \LogicException('Google authentication is handled by the authenticator.');
    }

    #[Route('/connect/github', name: 'oauth_github_start')]
    public function github(Request $request, GitHubAuthenticator $github): Response
    {
        if (!$github->isConfigured()) {
            $this->addFlash('warning', 'auth.oauth_unconfigured');

            return $this->redirectToRoute('app_login');
        }

        return $this->redirect($github->authorizationUrl($request));
    }

    #[Route('/connect/github/check', name: 'oauth_github_check')]
    public function githubCheck(): void
    {
        throw new \LogicException('GitHub authentication is handled by the authenticator.');
    }
}
