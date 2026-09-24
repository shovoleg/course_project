<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\AccessOperator;
use App\Entity\AttributeType;
use App\Entity\CandidateAttributeValue;
use App\Entity\CvAttribute;
use App\Entity\User;
use App\Service\RuleComparator;
use PHPUnit\Framework\TestCase;

final class RuleComparatorTest extends TestCase
{
    public function testNumericGreaterThan(): void
    {
        $value = $this->value(AttributeType::Numeric);
        $value->apply(['numeric' => '7.5'], null);
        $comparator = new RuleComparator();

        self::assertTrue($comparator->matches(AccessOperator::Gt, AttributeType::Numeric, $value, '7'));
        self::assertFalse($comparator->matches(AccessOperator::Gt, AttributeType::Numeric, $value, '8'));
    }

    public function testCheckedBoolean(): void
    {
        $value = $this->value(AttributeType::Boolean);
        $value->apply(['boolean' => '1'], null);
        $comparator = new RuleComparator();

        self::assertTrue($comparator->matches(AccessOperator::Checked, AttributeType::Boolean, $value, null));
        self::assertFalse($comparator->matches(AccessOperator::Unchecked, AttributeType::Boolean, $value, null));
    }

    public function testEmptyValueDoesNotMatchEquality(): void
    {
        $comparator = new RuleComparator();

        self::assertFalse($comparator->matches(AccessOperator::Eq, AttributeType::String, null, 'advanced'));
    }

    private function value(AttributeType $type): CandidateAttributeValue
    {
        $attribute = new CvAttribute();
        $attribute->setType($type);
        $attribute->setName('Sample');

        return new CandidateAttributeValue(new User(), $attribute);
    }
}
