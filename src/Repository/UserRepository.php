<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function qbSearchSort(?string $q, string $sort, string $dir): QueryBuilder
    {
        $allowedSort = ['id','username','email','roleType','status','createdAt'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';

        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('u');

        if ($q) {
            $qb->andWhere('u.username LIKE :q OR u.email LIKE :q OR u.status LIKE :q OR u.roleType LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        return $qb->orderBy('u.'.$sort, $dir);
    }
}
