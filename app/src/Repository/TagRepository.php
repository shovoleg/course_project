<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function suggest(string $prefix): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.name')
            ->andWhere('t.name LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findByNames(array $names): array
    {
        if ($names === []) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->andWhere('LOWER(t.name) IN (:names)')
            ->setParameter('names', array_map('mb_strtolower', $names))
            ->getQuery()
            ->getResult();
    }

    public function cloud(int $limit): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.id AS id, t.name AS name, COUNT(p.id) AS weight')
            ->leftJoin('t.projects', 'p')
            ->groupBy('t.id')
            ->addGroupBy('t.name')
            ->orderBy('t.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
