<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findForOwner(User $owner): array
    {
        return $this->createQueryBuilder('p')
            ->select('p', 't')
            ->leftJoin('p.tags', 't')
            ->andWhere('p.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.periodEnd', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function removeBlank(User $owner): void
    {
        $manager = $this->getEntityManager();
        $changed = false;
        foreach ($this->findForOwner($owner) as $project) {
            if ($project->isBlank()) {
                $manager->remove($project);
                $changed = true;
            }
        }
        if ($changed) {
            $manager->flush();
        }
    }
}
