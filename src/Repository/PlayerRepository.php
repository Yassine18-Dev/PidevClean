<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Team;
use App\Service\InvitationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Joueurs disponibles = sans team + pas déjà invités par cette team (pending)
     * @return Player[]
     */
    public function findAvailableForTeam(Team $team, ?string $q = null, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.team IS NULL');

        if ($q !== null && trim($q) !== '') {
            $qb->andWhere('LOWER(p.nickname) LIKE :q OR LOWER(p.name) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower(trim($q)) . '%');
        }

        // Exclure ceux déjà invités (pending) par cette team
        $qb->leftJoin('p.receivedInvitations', 'i', 'WITH', 'i.team = :t AND i.status = :s')
           ->setParameter('t', $team)
           ->setParameter('s', \App\Entity\Invitation::STATUS_PENDING)
           ->andWhere('i.id IS NULL');

        return $qb->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}