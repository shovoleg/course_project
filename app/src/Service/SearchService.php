<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Cv;
use App\Entity\CvStatus;
use App\Entity\Position;
use App\Entity\User;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvRepository;
use App\Repository\PositionRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

final class SearchService
{
    public function __construct(
        private Connection $connection,
        private SearchQuery $query,
        private PositionRepository $positions,
        private CvRepository $cvs,
        private CandidateAttributeValueRepository $values,
        private AccessEvaluator $access,
        private Security $security,
    ) {
    }

    public function search(string $text, ?User $viewer): array
    {
        $boolean = $this->query->boolean($text);
        if ($boolean === '') {
            return ['positions' => [], 'cvs' => [], 'query' => $text];
        }

        return [
            'positions' => $this->positionHits($boolean, $viewer),
            'cvs' => $this->cvHits($boolean, $viewer),
            'query' => $text,
        ];
    }

    private function positionHits(string $boolean, ?User $viewer): array
    {
        $ids = $this->connection->fetchFirstColumn(
            'SELECT p.id FROM job_position p WHERE MATCH(p.title, p.short_description, p.company) AGAINST (:q IN BOOLEAN MODE) ORDER BY p.updated_at DESC LIMIT 50',
            ['q' => $boolean]
        );
        $loaded = [];
        foreach ($this->loadPositions($ids) as $position) {
            if ($this->access->canBrowse($position, $viewer, $this->valuesFor($viewer))) {
                $loaded[] = $position;
            }
        }

        return $loaded;
    }

    private function cvHits(string $boolean, ?User $viewer): array
    {
        if (!$viewer instanceof User) {
            return [];
        }
        $ids = $this->connection->fetchFirstColumn(
            'SELECT id FROM (
                SELECT c.id AS id FROM cv c INNER JOIN candidate_attribute_value v ON v.user_id = c.owner_id WHERE MATCH(v.string_value, v.text_value) AGAINST (:q IN BOOLEAN MODE)
                UNION
                SELECT c.id AS id FROM cv c INNER JOIN project pr ON pr.owner_id = c.owner_id WHERE MATCH(pr.name, pr.description) AGAINST (:q IN BOOLEAN MODE)
                UNION
                SELECT c.id AS id FROM cv c INNER JOIN position_attribute pa ON pa.position_id = c.position_id INNER JOIN cv_attribute a ON a.id = pa.attribute_id WHERE MATCH(a.name, a.description) AGAINST (:q IN BOOLEAN MODE)
             ) hits LIMIT 50',
            ['q' => $boolean]
        );
        $cvs = $this->cvs->findByIds(array_map('intval', $ids));
        $ownerIds = array_map(static fn (Cv $cv): int => (int) $cv->getOwner()->getId(), $cvs);
        $attributeIds = [];
        foreach ($cvs as $cv) {
            foreach ($cv->getPosition()->getAccessRules() as $rule) {
                $attributeIds[] = (int) $rule->getAttribute()->getId();
            }
        }
        $grouped = $this->values->indexedForUsers(array_values(array_unique($ownerIds)), array_values(array_unique($attributeIds)));
        $visible = [];
        foreach ($cvs as $cv) {
            if (!$this->visible($cv, $viewer, $grouped[$cv->getOwner()->getId()] ?? [])) {
                continue;
            }
            $visible[] = $cv;
        }

        return $visible;
    }

    private function visible(Cv $cv, User $viewer, array $values): bool
    {
        if ($cv->getStatus() !== CvStatus::Published && !$this->security->isGranted('ROLE_ADMIN') && $cv->getOwner()->getId() !== $viewer->getId()) {
            return false;
        }

        return $this->access->canBrowse($cv->getPosition(), $cv->getOwner(), $values);
    }

    private function valuesFor(?User $viewer): array
    {
        if (!$viewer instanceof User || $this->security->isGranted('ROLE_RECRUITER')) {
            return [];
        }

        return $this->values->indexedForUser($viewer);
    }

    private function loadPositions(array $ids): array
    {
        $ids = array_map('intval', $ids);
        if ($ids === []) {
            return [];
        }
        $byId = [];
        foreach ($this->positions->findAllWithRules() as $position) {
            $byId[$position->getId()] = $position;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }
}
