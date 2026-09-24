<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Position::class);
    }

    public function findRecent(int $limit): array
    {
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        return $this->findWithRulesByIds($ids);
    }

    public function findAllWithRules(): array
    {
        $ids = $this->createQueryBuilder('p')
            ->select('p.id')
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();

        return $this->findWithRulesByIds($ids);
    }

    public function findOneDetailed(int $id): ?Position
    {
        $items = $this->findWithRulesByIds([$id]);

        return $items[0] ?? null;
    }

    public function popular(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id AS id, p.title AS title, COUNT(c.id) AS cvCount')
            ->leftJoin('p.cvs', 'c')
            ->groupBy('p.id')
            ->addGroupBy('p.title')
            ->orderBy('cvCount', 'DESC')
            ->addOrderBy('p.title', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function rows(?string $level, ?string $tag, string $sort, string $direction): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id AS id, p.title AS title, p.company AS company, p.level AS level, p.visibility AS visibility, p.updatedAt AS updatedAt, p.version AS version, COUNT(DISTINCT c.id) AS cvCount')
            ->leftJoin('p.cvs', 'c')
            ->groupBy('p.id')
            ->addGroupBy('p.title')
            ->addGroupBy('p.company')
            ->addGroupBy('p.level')
            ->addGroupBy('p.visibility')
            ->addGroupBy('p.updatedAt')
            ->addGroupBy('p.version');
        if ($level) {
            $qb->andWhere('p.level = :level')->setParameter('level', $level);
        }
        if ($tag) {
            $qb->join('p.projectTags', 'tag')->andWhere('tag.name = :tag')->setParameter('tag', $tag);
        }
        $column = $sort === 'title' ? 'p.title' : 'p.updatedAt';
        $qb->orderBy($column, $direction === 'ASC' ? 'ASC' : 'DESC');

        return $qb->getQuery()->getArrayResult();
    }

    private function findWithRulesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $items = $this->createQueryBuilder('p')
            ->select('p', 'r', 'a', 't', 'pa', 'attr', 'attrCat', 'attrOpt')
            ->leftJoin('p.accessRules', 'r')
            ->leftJoin('r.attribute', 'a')
            ->leftJoin('p.projectTags', 't')
            ->leftJoin('p.positionAttributes', 'pa')
            ->leftJoin('pa.attribute', 'attr')
            ->leftJoin('attr.category', 'attrCat')
            ->leftJoin('attr.options', 'attrOpt')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
        $byId = [];
        foreach ($items as $item) {
            $byId[$item->getId()] = $item;
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
