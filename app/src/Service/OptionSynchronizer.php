<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeOption;
use App\Entity\AttributeType;
use App\Entity\CvAttribute;

final class OptionSynchronizer
{
    public function sync(CvAttribute $attribute, string $text): void
    {
        if ($attribute->getType() !== AttributeType::OneOfMany) {
            foreach ($attribute->getOptions()->toArray() as $option) {
                $attribute->removeOption($option);
            }

            return;
        }
        $labels = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && mb_strlen($line) <= 180 && !in_array($line, $labels, true)) {
                $labels[] = $line;
            }
        }
        $existing = [];
        foreach ($attribute->getOptions() as $option) {
            $existing[$option->getLabel()] = $option;
        }
        foreach ($attribute->getOptions()->toArray() as $option) {
            if (!in_array($option->getLabel(), $labels, true)) {
                $attribute->removeOption($option);
            }
        }
        foreach ($labels as $index => $label) {
            $option = $existing[$label] ?? new AttributeOption($attribute, $label);
            $option->setSortOrder($index);
        }
    }
}
