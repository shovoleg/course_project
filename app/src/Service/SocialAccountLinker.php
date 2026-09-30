<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SocialAccount;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class SocialAccountLinker
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserFactory $users,
    ) {
    }

    public function link(string $provider, string $providerUserId, string $email): User
    {
        $email = mb_strtolower(trim($email));
        $existing = $this->em->getRepository(SocialAccount::class)->findOneBy([
            'provider' => $provider,
            'providerUserId' => $providerUserId,
        ]);
        if ($existing instanceof SocialAccount) {
            return $existing->getUser();
        }
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = $this->users->create($email, ['ROLE_CANDIDATE'], null);
            $user->setVerified(true);
            $this->em->persist($user);
        } elseif (!$user->isVerified()) {
            $user->setVerified(true);
            $user->setVerificationToken(null);
            $user->setVerificationExpiresAt(null);
        }
        new SocialAccount($user, $provider, $providerUserId);
        $this->em->flush();

        return $user;
    }
}
