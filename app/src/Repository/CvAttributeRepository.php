<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CvAttribute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CvAttributeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CvAttribute::class);
    }

    public function findLibrary(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'c', 'o')
            ->join('a.category', 'c')
            ->leftJoin('a.options', 'o')
            ->orderBy('c.code', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneWithOptions(int $id): ?CvAttribute
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'c', 'o')
            ->join('a.category', 'c')
            ->leftJoin('a.options', 'o')
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function builtins(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.builtin = true')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?CvAttribute
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function lookup(string $prefix, ?string $category, int $limit): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.id AS id, a.name AS name, a.type AS type, c.code AS category')
            ->join('a.category', 'c')
            ->orderBy('a.name', 'ASC')
            ->setMaxResults($limit);
        if ($prefix !== '') {
            $qb->andWhere('a.name LIKE :prefix')->setParameter('prefix', $prefix.'%');
        }
        if ($category) {
            $qb->andWhere('c.code = :category')->setParameter('category', $category);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function recent(int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS id, a.name AS name, a.type AS type, c.code AS category')
            ->join('a.category', 'c')
            ->andWhere('a.lastUsedAt IS NOT NULL')
            ->orderBy('a.lastUsedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select('a', 'o')
            ->leftJoin('a.options', 'o')
            ->andWhere('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
