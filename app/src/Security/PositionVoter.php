<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Position;
use App\Entity\User;
use App\Repository\CandidateAttributeValueRepository;
use App\Service\AccessEvaluator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PositionVoter extends Voter
{
    public const VIEW = 'POSITION_VIEW';
    public const EDIT = 'POSITION_EDIT';

    public function __construct(
        private AccessEvaluator $access,
        private CandidateAttributeValueRepository $values,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof Position;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($attribute === self::EDIT) {
            return $user instanceof User && ($this->has($user, 'ROLE_RECRUITER') || $this->has($user, 'ROLE_ADMIN'));
        }
        $values = [];
        if ($user instanceof User && !$this->has($user, 'ROLE_RECRUITER') && !$this->has($user, 'ROLE_ADMIN')) {
            $values = $this->values->indexedForUser($user);
        }

        return $this->access->canBrowse($subject, $user instanceof User ? $user : null, $values);
    }

    private function has(User $user, string $role): bool
    {
        return in_array($role, $user->getRoles(), true);
    }
}
