<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscussionPost;
use App\Entity\Position;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DiscussionPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscussionPost::class);
    }

    public function forPosition(Position $position, ?int $afterId = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'a')
            ->join('d.author', 'a')
            ->andWhere('d.position = :position')
            ->setParameter('position', $position)
            ->orderBy('d.id', 'ASC');
        if ($afterId) {
            $qb->andWhere('d.id > :after')->setParameter('after', $afterId);
        }

        return $qb->getQuery()->getResult();
    }
}
