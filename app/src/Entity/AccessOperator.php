<?php

declare(strict_types=1);

namespace App\Entity;

enum AccessOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case Contains = 'contains';
    case Checked = 'checked';
    case Unchecked = 'unchecked';

    public static function forType(AttributeType $type): array
    {
        return match ($type) {
            AttributeType::String => [self::Eq, self::Neq, self::Contains],
            AttributeType::Text => [self::Contains],
            AttributeType::Numeric, AttributeType::Date, AttributeType::Period => [self::Eq, self::Neq, self::Gt, self::Gte, self::Lt, self::Lte],
            AttributeType::Boolean => [self::Checked, self::Unchecked],
            AttributeType::OneOfMany => [self::Eq, self::Neq],
            AttributeType::Image => [],
        };
    }
}
