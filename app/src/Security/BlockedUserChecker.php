<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class BlockedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && $user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException('auth.blocked');
        }
        if ($user instanceof User && !$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('verify.not_verified');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
