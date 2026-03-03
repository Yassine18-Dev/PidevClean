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

    /**
     * Pour la page /players : récupérer tous les users avec leur Player et Team si existant
     */
    public function findUsersWithPlayers(?string $q, string $sort, string $dir)
    {
        $allowedSort = ['id','username'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.player', 'p')->addSelect('p')
            ->leftJoin('p.team', 't')->addSelect('t');

        if ($q) {
            $qb->andWhere('u.username LIKE :q OR p.nickname LIKE :q OR t.name LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        // Pour le tri, on mappe sur user.username ou user.id par défaut (puisque certains users n'auront pas de pseudo joueur)
        if ($sort === 'username') {
             $qb->orderBy('u.username', $dir);
        } else {
             $qb->orderBy('u.id', $dir);
        }

        return $qb->getQuery()->getResult();
    }
}
