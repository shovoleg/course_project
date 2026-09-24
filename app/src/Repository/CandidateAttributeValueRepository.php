<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CandidateAttributeValue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CandidateAttributeValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CandidateAttributeValue::class);
    }

    public function findForProfile(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->select('v', 'a', 'c', 'o')
            ->join('v.attribute', 'a')
            ->join('a.category', 'c')
            ->leftJoin('a.options', 'o')
            ->leftJoin('v.option', 'selected')
            ->addSelect('selected')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.builtin', 'DESC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function indexedForUser(User $user): array
    {
        $indexed = [];
        foreach ($this->findForProfile($user) as $value) {
            $indexed[$value->getAttribute()->getId()] = $value;
        }

        return $indexed;
    }

    public function findOneFor(User $user, int $attributeId): ?CandidateAttributeValue
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->andWhere('v.attribute = :attribute')
            ->setParameter('user', $user)
            ->setParameter('attribute', $attributeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function indexedForUsers(array $userIds, array $attributeIds): array
    {
        if ($userIds === [] || $attributeIds === []) {
            return [];
        }
        $rows = $this->createQueryBuilder('v')
            ->select('v', 'a', 'o')
            ->join('v.attribute', 'a')
            ->leftJoin('v.option', 'o')
            ->andWhere('v.user IN (:users)')
            ->andWhere('v.attribute IN (:attributes)')
            ->setParameter('users', $userIds)
            ->setParameter('attributes', $attributeIds)
            ->getQuery()
            ->getResult();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->getUser()->getId()][$row->getAttribute()->getId()] = $row;
        }

        return $indexed;
    }

    public function namesFor(array $userIds, int $firstNameId, int $lastNameId): array
    {
        if ($userIds === []) {
            return [];
        }
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.user) AS userId, IDENTITY(v.attribute) AS attributeId, v.stringValue AS stringValue')
            ->andWhere('v.user IN (:users)')
            ->andWhere('v.attribute IN (:attributes)')
            ->setParameter('users', $userIds)
            ->setParameter('attributes', [$firstNameId, $lastNameId])
            ->getQuery()
            ->getArrayResult();
        $names = [];
        foreach ($rows as $row) {
            $names[(int) $row['userId']][(int) $row['attributeId']] = (string) $row['stringValue'];
        }

        return $names;
    }

    public function numericForUsers(array $userIds, array $attributeIds): array
    {
        if ($userIds === [] || $attributeIds === []) {
            return [];
        }

        return $this->createQueryBuilder('v')
            ->select('IDENTITY(v.attribute) AS attributeId, a.name AS name, AVG(v.numericValue) AS averageValue, COUNT(v.id) AS filled')
            ->join('v.attribute', 'a')
            ->andWhere('v.user IN (:users)')
            ->andWhere('v.attribute IN (:attributes)')
            ->andWhere('v.numericValue IS NOT NULL')
            ->setParameter('users', $userIds)
            ->setParameter('attributes', $attributeIds)
            ->groupBy('v.attribute')
            ->addGroupBy('a.name')
            ->getQuery()
            ->getArrayResult();
    }
}
