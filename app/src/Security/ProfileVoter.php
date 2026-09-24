<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProfileVoter extends Voter
{
    public const EDIT = 'PROFILE_EDIT';
    public const PUBLIC_VIEW = 'PUBLIC_PROFILE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::PUBLIC_VIEW], true) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        $admin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        if ($attribute === self::PUBLIC_VIEW) {
            return $admin || in_array('ROLE_RECRUITER', $user->getRoles(), true);
        }

        return $admin || $user->getId() === $subject->getId();
    }
}
