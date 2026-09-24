<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Position;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\ProjectRepository;

final class ProjectSelector
{
    public function __construct(private ProjectRepository $projects)
    {
    }

    public function forPosition(User $owner, Position $position): array
    {
        $required = [];
        foreach ($position->getProjectTags() as $tag) {
            $required[] = mb_strtolower($tag->getName());
        }
        $matched = [];
        foreach ($this->projects->findForOwner($owner) as $project) {
            if ($this->matches($project, $required)) {
                $matched[] = $project;
            }
        }
        usort($matched, function (Project $left, Project $right): int {
            $end = ($right->getPeriodEnd()?->getTimestamp() ?? 0) <=> ($left->getPeriodEnd()?->getTimestamp() ?? 0);
            if ($end !== 0) {
                return $end;
            }

            return $right->getCreatedAt() <=> $left->getCreatedAt();
        });

        return array_slice($matched, 0, max(0, $position->getMaxProjects()));
    }

    private function matches(Project $project, array $required): bool
    {
        if ($required === []) {
            return true;
        }
        $names = array_map(static fn (Tag $tag): string => mb_strtolower($tag->getName()), $project->getTags()->toArray());

        return array_diff($required, $names) === [];
    }
}
