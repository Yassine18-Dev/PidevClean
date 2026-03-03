<?php

namespace App\Repository;

use App\Entity\Invitation;
use App\Entity\Player;
use App\Entity\Team;
use App\Service\InvitationService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function hasPendingInvite(Team $team, Player $player, ?string $type = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.team = :t')->setParameter('t', $team)
            ->andWhere('i.player = :p')->setParameter('p', $player)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING);

        if ($type) {
            $qb->andWhere('i.type = :type')->setParameter('type', $type);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function countSentByTeamLast24h(Team $team): int
    {
        $since = new \DateTimeImmutable('-24 hours');

        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.team = :t')->setParameter('t', $team)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->andWhere('i.createdAt >= :since')->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult();
    }

    /** @return Invitation[] */
    public function findPendingOlderThan(\DateTimeImmutable $threshold): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->andWhere('i.createdAt < :th')->setParameter('th', $threshold)
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return Invitation[] */
    public function findOtherPendingInvitesForPlayer(Player $player, Invitation $exclude): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.player = :p')->setParameter('p', $player)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->andWhere('i.id <> :id')->setParameter('id', $exclude->getId())
            ->getQuery()->getResult();
    }

    /** @return Invitation[] */
    public function findPendingForPlayer(Player $player): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.player = :p')->setParameter('p', $player)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            // Pour le joueur, on affiche surtout les invitations envoyées par les capitaines
            ->andWhere('i.type = :type')->setParameter('type', Invitation::TYPE_INVITATION)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return Invitation[] */
    public function findPendingForTeam(Team $team): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.team = :t')->setParameter('t', $team)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return Invitation[] */
    public function findPendingCandidaturesForTeam(Team $team): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.team = :t')->setParameter('t', $team)
            ->andWhere('i.status = :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->andWhere('i.type = :type')->setParameter('type', Invitation::TYPE_CANDIDATURE)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return Invitation[] */
    public function findHistoryForTeam(Team $team, int $limit = 50): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.team = :t')->setParameter('t', $team)
            ->andWhere('i.status <> :s')->setParameter('s', Invitation::STATUS_PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}