<?php

namespace App\Repository;

use App\Entity\Invitation;
use App\Entity\Player;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invitation>
 */
class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    /** @return Invitation[] */
    public function findPendingForTeam(Team $team): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.player', 'p')->addSelect('p')
            ->andWhere('i.team = :team')
            ->andWhere('i.status = :st')
            ->setParameter('team', $team)
            ->setParameter('st', Invitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Invitation[] */
    public function findReceivedPending(Player $player): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.team', 't')->addSelect('t')
            ->leftJoin('i.invitedBy', 'ib')->addSelect('ib')
            ->andWhere('i.player = :p')
            ->andWhere('i.status = :st')
            ->setParameter('p', $player)
            ->setParameter('st', Invitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countSentToday(Player $captain): int
    {
        $start = (new \DateTimeImmutable('today'))->setTime(0, 0);

        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.invitedBy = :c')
            ->andWhere('i.createdAt >= :start')
            ->setParameter('c', $captain)
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPending(Team $team, Player $player): ?Invitation
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.team = :t')
            ->andWhere('i.player = :p')
            ->andWhere('i.status = :st')
            ->setParameter('t', $team)
            ->setParameter('p', $player)
            ->setParameter('st', Invitation::STATUS_PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
