<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccessOperator;
use App\Entity\AttributeType;
use App\Entity\CvAttribute;
use App\Entity\Position;
use App\Entity\PositionAccessRule;
use App\Entity\PositionAttribute;
use App\Repository\CvAttributeRepository;
use Doctrine\ORM\EntityManagerInterface;

final class PositionEditor
{
    public function __construct(
        private EntityManagerInterface $em,
        private CvAttributeRepository $attributes,
        private TagFactory $tags,
    ) {
    }

    public function sync(Position $position, array $attributeIds, array $tagNames, array $rules): void
    {
        $this->syncAttributes($position, $attributeIds);
        $position->replaceProjectTags($this->tags->fromNames($tagNames));
        $this->syncRules($position, $rules);
        $position->touch();
    }

    public function duplicate(Position $source, string $title): Position
    {
        $copy = new Position();
        $copy->setTitle($title);
        $copy->setShortDescription($source->getShortDescription());
        $copy->setCompany($source->getCompany());
        $copy->setLevel($source->getLevel());
        $copy->setVisibility($source->getVisibility());
        $copy->setMaxProjects($source->getMaxProjects());
        $this->em->persist($copy);
        foreach ($source->getPositionAttributes() as $link) {
            new PositionAttribute($copy, $link->getAttribute(), $link->getSortOrder());
            $link->getAttribute()->markUsed();
        }
        $copy->replaceProjectTags($source->getProjectTags()->toArray());
        foreach ($source->getAccessRules() as $rule) {
            new PositionAccessRule($copy, $rule->getAttribute(), $rule->getOperator(), $rule->getComparison());
        }

        return $copy;
    }

    private function syncAttributes(Position $position, array $attributeIds): void
    {
        $ids = [];
        foreach ($attributeIds as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        $found = [];
        foreach ($this->attributes->findByIds($ids) as $attribute) {
            $found[$attribute->getId()] = $attribute;
        }
        $existing = [];
        foreach ($position->getPositionAttributes() as $link) {
            $existing[$link->getAttribute()->getId()] = $link;
        }
        foreach ($position->getPositionAttributes()->toArray() as $link) {
            if (!in_array($link->getAttribute()->getId(), $ids, true)) {
                $position->removePositionAttribute($link);
                $this->em->remove($link);
            }
        }
        foreach ($ids as $index => $id) {
            if (!isset($found[$id])) {
                continue;
            }
            if (isset($existing[$id])) {
                $existing[$id]->setSortOrder($index);
                continue;
            }
            new PositionAttribute($position, $found[$id], $index);
            $found[$id]->markUsed();
        }
    }

    private function syncRules(Position $position, array $rules): void
    {
        foreach ($position->getAccessRules()->toArray() as $rule) {
            $position->removeAccessRule($rule);
            $this->em->remove($rule);
        }
        $allowed = [];
        foreach ($position->getPositionAttributes() as $link) {
            $allowed[$link->getAttribute()->getId()] = $link->getAttribute();
        }
        foreach ($rules as $row) {
            if (!is_array($row)) {
                continue;
            }
            $attribute = $allowed[(int) ($row['attribute'] ?? 0)] ?? null;
            $operator = AccessOperator::tryFrom((string) ($row['operator'] ?? ''));
            if (!$attribute instanceof CvAttribute || !$operator instanceof AccessOperator) {
                continue;
            }
            if (!in_array($operator, AccessOperator::forType($attribute->getType()), true)) {
                continue;
            }
            $comparison = trim((string) ($row['value'] ?? ''));
            if ($attribute->getType() !== AttributeType::Boolean && $comparison === '') {
                continue;
            }
            if ($attribute->getType() === AttributeType::OneOfMany && !$this->optionBelongs($attribute, $comparison)) {
                continue;
            }
            new PositionAccessRule($position, $attribute, $operator, $comparison === '' ? null : $comparison);
        }
    }

    private function optionBelongs(CvAttribute $attribute, string $optionId): bool
    {
        foreach ($attribute->getOptions() as $option) {
            if ((string) $option->getId() === $optionId) {
                return true;
            }
        }

        return false;
    }
}
