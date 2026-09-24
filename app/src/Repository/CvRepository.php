<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cv;
use App\Entity\Position;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cv::class);
    }

    public function findForDocument(int $id): ?Cv
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'u', 'p', 'pa', 'a', 'cat', 'o', 't')
            ->join('c.owner', 'u')
            ->join('c.position', 'p')
            ->leftJoin('p.positionAttributes', 'pa')
            ->leftJoin('pa.attribute', 'a')
            ->leftJoin('a.category', 'cat')
            ->leftJoin('a.options', 'o')
            ->leftJoin('p.projectTags', 't')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneFor(User $owner, Position $position): ?Cv
    {
        return $this->findOneBy(['owner' => $owner, 'position' => $position]);
    }

    public function findForPosition(Position $position): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'u')
            ->join('c.owner', 'u')
            ->andWhere('c.position = :position')
            ->setParameter('position', $position)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findForOwner(User $owner): array
    {
        return $this->createQueryBuilder('c')
            ->select('c', 'p', 'r', 'a')
            ->join('c.position', 'p')
            ->leftJoin('p.accessRules', 'r')
            ->leftJoin('r.attribute', 'a')
            ->andWhere('c.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->select('c', 'u', 'p', 'r', 'a')
            ->join('c.owner', 'u')
            ->join('c.position', 'p')
            ->leftJoin('p.accessRules', 'r')
            ->leftJoin('r.attribute', 'a')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function likeCounts(array $cvIds): array
    {
        if ($cvIds === []) {
            return [];
        }
        $rows = $this->createQueryBuilder('c')
            ->select('c.id AS id, COUNT(l.id) AS likes')
            ->leftJoin('c.likes', 'l')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $cvIds)
            ->groupBy('c.id')
            ->getQuery()
            ->getArrayResult();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['id']] = (int) $row['likes'];
        }

        return $counts;
    }

    public function likedBy(User $user, array $cvIds): array
    {
        if ($cvIds === []) {
            return [];
        }
        $rows = $this->getEntityManager()->createQuery(
            'SELECT IDENTITY(l.cv) FROM App\Entity\CvLike l WHERE l.recruiter = :user AND l.cv IN (:ids)'
        )->setParameter('user', $user)->setParameter('ids', $cvIds)->getSingleColumnResult();

        return array_map('intval', $rows);
    }

    public function countSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
