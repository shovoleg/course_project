<?php

declare(strict_types=1);

namespace App\Service;

final class SearchQuery
{
    public function boolean(string $query): string
    {
        $parts = preg_split('/\s+/u', trim($query)) ?: [];
        $terms = [];
        foreach ($parts as $part) {
            $clean = preg_replace('/[+\-<>\(\)~*"@]+/u', '', $part) ?? '';
            $clean = trim($clean);
            if (mb_strlen($clean) < 2) {
                continue;
            }
            $terms[] = '+'.$clean.'*';
        }

        return implode(' ', $terms);
    }
}
