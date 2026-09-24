<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeOption;
use App\Entity\CandidateAttributeValue;
use App\Entity\CvAttribute;
use App\Entity\Project;
use App\Entity\User;
use App\Exception\VersionConflict;
use App\Repository\CandidateAttributeValueRepository;
use App\Repository\CvAttributeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

final class ProfileSaver
{
    public function __construct(
        private EntityManagerInterface $em,
        private CandidateAttributeValueRepository $values,
        private CvAttributeRepository $attributes,
        private ProjectRepository $projects,
        private TagFactory $tags,
        private FieldValidator $fields,
    ) {
    }

    public function save(User $owner, array $payload): array
    {
        try {
            return $this->em->wrapInTransaction(function () use ($owner, $payload): array {
                [$values, $valueErrors] = $this->saveValues($owner, $payload['values'] ?? []);
                [$projects, $projectErrors] = $this->saveProjects($owner, $payload['projects'] ?? []);

                return [
                    'values' => $values,
                    'projects' => $projects,
                    'errors' => array_merge($valueErrors, $projectErrors),
                ];
            });
        } catch (OptimisticLockException) {
            throw new VersionConflict();
        }
    }

    private function saveValues(User $owner, array $rows): array
    {
        $errors = [];
        $ids = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $ids[] = (int) ($row['attributeId'] ?? 0);
            }
        }
        $found = [];
        foreach ($this->attributes->findByIds($ids) as $attribute) {
            $found[$attribute->getId()] = $attribute;
        }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $attributeId = (int) ($row['attributeId'] ?? 0);
            $attribute = $found[$attributeId] ?? null;
            if (!$attribute instanceof CvAttribute) {
                continue;
            }
            $value = $this->values->findOneFor($owner, $attributeId);
            $submitted = (int) ($row['version'] ?? 0);
            if ($value instanceof CandidateAttributeValue && $submitted !== $value->getVersion()) {
                throw new VersionConflict();
            }
            $option = $this->option($attribute, $row['optionId'] ?? null);
            $messages = $this->fields->valueErrors($attribute, $row, $option);
            if ($messages !== []) {
                $errors[] = ['attributeId' => $attributeId, 'message' => $messages[0]];
                continue;
            }
            $value->apply($row, $option);
            $attribute->markUsed();
            $this->em->flush();
            $result[] = ['attributeId' => $attributeId, 'version' => $value->getVersion()];
        }

        return [$result, $errors];
    }

    private function saveProjects(User $owner, array $rows): array
    {
        $errors = [];
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int) ($row['id'] ?? 0);
            $project = $id > 0 ? $this->projects->find($id) : null;
            if ($project instanceof Project && $project->getOwner()->getId() !== $owner->getId()) {
                continue;
            }
            if ($this->blankRow($row)) {
                if ($project instanceof Project) {
                    $this->em->remove($project);
                    $this->em->flush();
                }
                continue;
            }
            if ($project instanceof Project) {
                if ((int) ($row['version'] ?? 0) !== $project->getVersion()) {
                    throw new VersionConflict();
                }
            } else {
                $project = new Project($owner);
            }
            $names = is_array($row['tags'] ?? null) ? $row['tags'] : [];
            $tagErrors = $this->fields->tagErrors($names);
            $probe = new Project($owner);
            $probe->setName((string) ($row['name'] ?? ''));
            $probe->setDescription($row['description'] ?? null);
            $probe->setPeriodStart($this->date($row['periodStart'] ?? null));
            $probe->setPeriodEnd($this->date($row['periodEnd'] ?? null));
            $messages = array_merge($tagErrors, $this->fields->projectErrors($probe));
            if ($messages !== []) {
                $errors[] = ['clientKey' => (string) ($row['clientKey'] ?? $project->getId()), 'message' => $messages[0]];
                continue;
            }
            if ($project->getId() === null) {
                $this->em->persist($project);
            }
            $project->setName($probe->getName());
            $project->setDescription($probe->getDescription());
            $project->setPeriodStart($probe->getPeriodStart());
            $project->setPeriodEnd($probe->getPeriodEnd());
            $project->replaceTags($this->tags->fromNames($names));
            $project->touch();
            $this->em->flush();
            $result[] = [
                'id' => $project->getId(),
                'clientKey' => (string) ($row['clientKey'] ?? $project->getId()),
                'version' => $project->getVersion(),
            ];
        }

        return [$result, $errors];
    }

    private function blankRow(array $row): bool
    {
        $names = is_array($row['tags'] ?? null) ? $row['tags'] : [];
        foreach ($names as $name) {
            if (trim((string) $name) !== '') {
                return false;
            }
        }

        return trim((string) ($row['name'] ?? '')) === ''
            && trim((string) ($row['description'] ?? '')) === ''
            && trim((string) ($row['periodStart'] ?? '')) === ''
            && trim((string) ($row['periodEnd'] ?? '')) === '';
    }

    private function option(CvAttribute $attribute, mixed $optionId): ?AttributeOption
    {
        $optionId = (int) $optionId;
        if ($optionId <= 0) {
            return null;
        }
        foreach ($attribute->getOptions() as $option) {
            if ($option->getId() === $optionId) {
                return $option;
            }
        }

        return null;
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }
}
