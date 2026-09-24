<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccessOperator;
use App\Entity\AttributeType;
use App\Entity\CandidateAttributeValue;

final class RuleComparator
{
    public function matches(AccessOperator $operator, AttributeType $type, ?CandidateAttributeValue $value, ?string $comparison): bool
    {
        if ($operator === AccessOperator::Checked) {
            return $value?->getBooleanValue() === true;
        }
        if ($operator === AccessOperator::Unchecked) {
            return $value?->getBooleanValue() !== true;
        }
        if ($value === null || $value->isEmpty() || $comparison === null || $comparison === '') {
            return false;
        }

        return match ($type) {
            AttributeType::Numeric => $this->compareNumber($operator, (float) $value->getNumericValue(), (float) $comparison),
            AttributeType::String => $this->compareText($operator, (string) $value->getStringValue(), $comparison),
            AttributeType::Text => $operator === AccessOperator::Contains && str_contains(mb_strtolower((string) $value->getTextValue()), mb_strtolower($comparison)),
            AttributeType::Date => $this->compareDate($operator, $value->getDateValue(), $comparison),
            AttributeType::Period => $this->compareDate($operator, $value->getPeriodStart(), $comparison),
            AttributeType::OneOfMany => $this->compareIdentity($operator, (string) $value->getOption()?->getId(), $comparison),
            AttributeType::Boolean, AttributeType::Image => false,
        };
    }

    private function compareNumber(AccessOperator $operator, float $left, float $right): bool
    {
        return match ($operator) {
            AccessOperator::Eq => abs($left - $right) < 0.0001,
            AccessOperator::Neq => abs($left - $right) >= 0.0001,
            AccessOperator::Gt => $left > $right,
            AccessOperator::Gte => $left >= $right,
            AccessOperator::Lt => $left < $right,
            AccessOperator::Lte => $left <= $right,
            default => false,
        };
    }

    private function compareText(AccessOperator $operator, string $left, string $right): bool
    {
        $left = mb_strtolower($left);
        $right = mb_strtolower($right);

        return match ($operator) {
            AccessOperator::Eq => $left === $right,
            AccessOperator::Neq => $left !== $right,
            AccessOperator::Contains => str_contains($left, $right),
            default => false,
        };
    }

    private function compareDate(AccessOperator $operator, ?\DateTimeImmutable $left, string $right): bool
    {
        if ($left === null) {
            return false;
        }
        $other = \DateTimeImmutable::createFromFormat('!Y-m-d', $right);
        if ($other === false) {
            return false;
        }
        $result = $left->format('Y-m-d') <=> $other->format('Y-m-d');

        return match ($operator) {
            AccessOperator::Eq => $result === 0,
            AccessOperator::Neq => $result !== 0,
            AccessOperator::Gt => $result > 0,
            AccessOperator::Gte => $result >= 0,
            AccessOperator::Lt => $result < 0,
            AccessOperator::Lte => $result <= 0,
            default => false,
        };
    }

    private function compareIdentity(AccessOperator $operator, string $left, string $right): bool
    {
        return match ($operator) {
            AccessOperator::Eq => $left === $right,
            AccessOperator::Neq => $left !== $right,
            default => false,
        };
    }
}
