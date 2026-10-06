<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeType;
use App\Entity\Position;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvRepository;

final class PositionAggregationService
{
    public function __construct(private CvRepository $cvs, private CandidateAttributeValueRepository $values) {}

    public function aggregate(Position $position): array
    {
        $attributes = [];
        foreach ($position->getPositionAttributes() as $pa) {
            $attr = $pa->getAttribute();
            $users = [];
            foreach ($position->getCvs() as $cv) {
                $u = $cv->getOwner();
                if ($u) $users[$u->getId()] = $u;
            }
            $userIds = array_keys($users);
            $vals = $userIds ? $this->values->createQueryBuilder('v')->andWhere('v.attribute = :a')->andWhere('v.user IN (:users)')->setParameter('a', $attr)->setParameter('users', $userIds)->getQuery()->getResult() : [];
            $agg = $this->compute($attr->getType(), $vals);
            $attributes[] = [
                'id' => $attr->getId(),
                'title' => $attr->getName(),
                'code' => $attr->getCode(),
                'type' => $attr->getType()->value,
                'category' => $attr->getCategory()?->getCode(),
                'aggregated' => $agg,
                'totalValues' => count(array_filter($vals, fn($v) => !$v->isEmpty())),
            ];
        }
        return [
            'position' => [
                'id' => $position->getId(),
                'title' => $position->getTitle(),
                'company' => $position->getCompany(),
                'level' => $position->getLevel()?->value,
                'updatedAt' => $position->getUpdatedAt()->format('c'),
                'cvCount' => $position->getCvs()->count(),
            ],
            'attributes' => $attributes,
        ];
    }

    private function compute(AttributeType $type, array $vals): array
    {
        $nonEmpty = array_filter($vals, fn($v) => !$v->isEmpty());
        if (!$nonEmpty) return ['count' => 0];
        return match($type) {
            AttributeType::Numeric => $this->numeric($nonEmpty),
            AttributeType::String, AttributeType::Text => $this->text($nonEmpty),
            AttributeType::OneOfMany => $this->oneOfMany($nonEmpty),
            AttributeType::Boolean => $this->boolAgg($nonEmpty),
            AttributeType::Date => $this->dateAgg($nonEmpty),
            AttributeType::Period => $this->periodAgg($nonEmpty),
            default => ['count' => count($nonEmpty)],
        };
    }

    private function numeric(array $vals): array
    {
        $nums = array_map(fn($v) => (float)$v->getNumericValue(), array_filter($vals, fn($v) => !$v->isEmpty()));
        sort($nums);
        $sum = array_sum($nums);
        $c = count($nums);
        return ['count' => $c, 'avg' => $c ? round($sum/$c, 2) : null, 'min' => $c ? min($nums) : null, 'max' => $c ? max($nums) : null, 'sum' => $sum];
    }

    private function text(array $vals): array
    {
        $texts = [];
        foreach ($vals as $v) {
            $t = $v->getStringValue() ?? $v->getTextValue() ?? '';
            $t = trim((string)$t);
            if ($t !== '') $texts[] = mb_strtolower($t);
        }
        $cnt = array_count_values($texts);
        arsort($cnt);
        $top = array_slice($cnt, 0, 5, true);
        $popular = [];
        foreach ($top as $k => $c) $popular[] = ['value' => $k, 'count' => $c];
        return ['count' => count($texts), 'distinct' => count($cnt), 'popular' => $popular];
    }

    private function oneOfMany(array $vals): array
    {
        $cnt = [];
        foreach ($vals as $v) {
            $opt = $v->getOption()?->getTitle() ?? '—';
            $cnt[$opt] = ($cnt[$opt] ?? 0) + 1;
        }
        arsort($cnt);
        $popular = [];
        foreach ($cnt as $k => $c) $popular[] = ['value' => $k, 'count' => $c];
        return ['count' => count($vals), 'options' => $popular];
    }

    private function boolAgg(array $vals): array
    {
        $t = 0; $f = 0;
        foreach ($vals as $v) $v->getBooleanValue() ? $t++ : $f++;
        return ['count' => count($vals), 'true' => $t, 'false' => $f];
    }

    private function dateAgg(array $vals): array
    {
        $dates = array_map(fn($v) => $v->getDateValue(), $vals);
        $dates = array_filter($dates);
        if (!$dates) return ['count' => 0];
        usort($dates, fn($a,$b) => $a<=>$b);
        return ['count' => count($dates), 'min' => $dates[0]->format('Y-m-d'), 'max' => end($dates)->format('Y-m-d')];
    }

    private function periodAgg(array $vals): array
    {
        $days = [];
        foreach ($vals as $v) {
            if ($v->getPeriodStart() && $v->getPeriodEnd()) $days[] = $v->getPeriodEnd()->diff($v->getPeriodStart())->days;
        }
        if (!$days) return ['count' => 0];
        sort($days);
        $sum = array_sum($days);
        $c = count($days);
        return ['count' => $c, 'avgDays' => round($sum/$c,1), 'minDays' => min($days), 'maxDays' => max($days)];
    }
}
