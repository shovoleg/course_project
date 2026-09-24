<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TagFactory
{
    public function __construct(
        private TagRepository $tags,
        private EntityManagerInterface $em,
    ) {
    }

    public function fromNames(array $names): array
    {
        $clean = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '' || mb_strlen($name) > 64) {
                continue;
            }
            $clean[mb_strtolower($name)] = $name;
        }
        if ($clean === []) {
            return [];
        }
        $existing = $this->tags->findByNames(array_values($clean));
        $byLower = [];
        foreach ($existing as $tag) {
            $byLower[mb_strtolower($tag->getName())] = $tag;
        }
        $result = [];
        foreach ($clean as $lower => $name) {
            if (!isset($byLower[$lower])) {
                $tag = new Tag($name);
                $this->em->persist($tag);
                $byLower[$lower] = $tag;
            }
            $result[] = $byLower[$lower];
        }

        return $result;
    }
}
