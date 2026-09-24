<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Cv;
use App\Entity\CvStatus;
use App\Entity\User;
use App\Repository\CandidateAttributeValueRepository;
use App\Service\AccessEvaluator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CvVoter extends Voter
{
    public const VIEW = 'CV_VIEW';
    public const EDIT = 'CV_EDIT';
    public const LIKE = 'CV_LIKE';

    public function __construct(
        private AccessEvaluator $access,
        private CandidateAttributeValueRepository $values,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::LIKE], true) && $subject instanceof Cv;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        $admin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $owner = $subject->getOwner()->getId() === $user->getId();
        $values = $this->values->indexedForUser($subject->getOwner());
        $visible = $admin || $this->access->canBrowse($subject->getPosition(), $subject->getOwner(), $values);
        if (!$visible) {
            return false;
        }
        if ($attribute === self::EDIT) {
            return $admin || $owner;
        }
        if ($attribute === self::LIKE) {
            return in_array('ROLE_RECRUITER', $user->getRoles(), true) || $admin;
        }
        if ($subject->getStatus() !== CvStatus::Published && !$admin && !$owner) {
            return false;
        }

        return $admin || $owner || in_array('ROLE_RECRUITER', $user->getRoles(), true);
    }
}
