<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Entity\PositionVisibility;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

final class AccessEvaluator
{
    public function __construct(
        private RuleComparator $rules,
        private Security $security,
    ) {
    }

    public function canBrowse(Position $position, ?User $viewer, array $valuesByAttributeId = []): bool
    {
        if ($this->security->isGranted('ROLE_RECRUITER')) {
            return true;
        }
        if ($position->getVisibility() === PositionVisibility::Public) {
            return true;
        }
        if (!$viewer instanceof User) {
            return false;
        }
        if ($position->getAccessRules()->isEmpty()) {
            return false;
        }
        foreach ($position->getAccessRules() as $rule) {
            $value = $valuesByAttributeId[$rule->getAttribute()->getId()] ?? null;
            if (!$this->rules->matches($rule->getOperator(), $rule->getAttribute()->getType(), $value, $rule->getComparison())) {
                return false;
            }
        }

        return true;
    }
}
