<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeType;
use App\Entity\CandidateAttributeValue;
use App\Entity\Cv;
use App\Entity\CvAttribute;
use App\Repository\CandidateAttributeValueRepository;

final class CvAssembler
{
    public function __construct(
        private CandidateAttributeValueRepository $values,
        private ProjectSelector $projects,
    ) {
    }

    public function present(Cv $cv): array
    {
        $stored = $this->values->indexedForUser($cv->getOwner());
        $sections = [];
        $complete = true;
        $name = [];
        $location = null;
        $photo = null;
        foreach ($cv->getPosition()->getPositionAttributes() as $link) {
            $attribute = $link->getAttribute();
            $value = $stored[$attribute->getId()] ?? null;
            $empty = !$value instanceof CandidateAttributeValue || $value->isEmpty();
            if ($empty) {
                $complete = false;
            }
            if ($attribute->getCode() === 'first_name' || $attribute->getCode() === 'last_name') {
                $name[] = $value?->getStringValue();
            }
            if ($attribute->getCode() === 'location') {
                $location = $value?->getStringValue();
            }
            if ($attribute->getCode() === 'personal_photo') {
                $photo = $value?->getImageUrl();
            }
            $code = $attribute->getCategory()?->getCode() ?? 'other';
            $sections[$code][] = [
                'attribute' => $attribute,
                'value' => $value,
                'empty' => $empty,
                'text' => $this->text($attribute, $value),
            ];
        }
        $display = trim(implode(' ', array_filter($name)));

        return [
            'sections' => $sections,
            'projects' => $this->projects->forPosition($cv->getOwner(), $cv->getPosition()),
            'complete' => $complete,
            'name' => $display !== '' ? $display : $cv->getOwner()->getEmail(),
            'location' => $location,
            'photo' => $photo,
        ];
    }

    private function text(CvAttribute $attribute, ?CandidateAttributeValue $value): string
    {
        if (!$value instanceof CandidateAttributeValue || $value->isEmpty()) {
            return '';
        }

        return match ($attribute->getType()) {
            AttributeType::String => (string) $value->getStringValue(),
            AttributeType::Text => (string) $value->getTextValue(),
            AttributeType::Image => (string) $value->getImageUrl(),
            AttributeType::Numeric => rtrim(rtrim((string) $value->getNumericValue(), '0'), '.'),
            AttributeType::Date => $value->getDateValue()?->format('Y-m-d') ?? '',
            AttributeType::Period => trim(($value->getPeriodStart()?->format('Y-m-d') ?? '').' — '.($value->getPeriodEnd()?->format('Y-m-d') ?? ''), ' —'),
            AttributeType::Boolean => $value->getBooleanValue() ? '1' : '0',
            AttributeType::OneOfMany => (string) $value->getOption()?->getLabel(),
        };
    }
}
