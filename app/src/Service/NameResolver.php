<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvAttributeRepository;

final class NameResolver
{
    private ?int $firstId = null;
    private ?int $lastId = null;

    public function __construct(
        private CandidateAttributeValueRepository $values,
        private CvAttributeRepository $attributes,
    ) {
    }

    public function forUsers(array $users): array
    {
        $ids = [];
        foreach ($users as $user) {
            if ($user instanceof User && $user->getId()) {
                $ids[] = $user->getId();
            }
        }
        $this->loadIds();
        if ($this->firstId === null || $this->lastId === null) {
            return [];
        }
        $stored = $this->values->namesFor($ids, $this->firstId, $this->lastId);
        $names = [];
        foreach ($users as $user) {
            if (!$user instanceof User || !$user->getId()) {
                continue;
            }
            $parts = array_filter([
                $stored[$user->getId()][$this->firstId] ?? '',
                $stored[$user->getId()][$this->lastId] ?? '',
            ]);
            $name = trim(implode(' ', $parts));
            $names[$user->getId()] = $name !== '' ? $name : $user->getEmail();
        }

        return $names;
    }

    private function loadIds(): void
    {
        if ($this->firstId !== null) {
            return;
        }
        $this->firstId = $this->attributes->findByCode('first_name')?->getId();
        $this->lastId = $this->attributes->findByCode('last_name')?->getId();
    }
}
