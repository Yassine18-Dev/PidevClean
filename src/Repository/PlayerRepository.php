<?php

namespace App\Repository;

use App\Entity\Player;
use App\Entity\Team;
use App\Entity\Invitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Simple search used for invitations.
     *
     * @return Player[]
     */
    public function searchByNickname(string $q, int $limit = 10): array
    {
        $q = trim(mb_strtolower($q));
        if ($q === '') {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.nickname) LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Player[] Returns an array of Player objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Player
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


/**
 * Search players that can be invited for the given team:
 * - same game
 * - no team (for lol/valorant)
 * - NOT already invited (pending) by this team
 *
 * @return Player[]
 */
public function searchAvailableForTeam(Team $team, string $q = '', int $limit = 10): array
{
    $qb = $this->createQueryBuilder('p');

    // same game
    $qb->andWhere('p.game = :g')->setParameter('g', $team->getGame());

    // for LoL/Valorant: must be without team
    if (in_array($team->getGame(), ['lol','valorant'], true)) {
        $qb->andWhere('p.team IS NULL');
    }

    // exclude players already invited pending by this team
    $qb->leftJoin('p.receivedInvitations', 'ri', 'WITH', 'ri.team = :t AND ri.status = :st')
       ->andWhere('ri.id IS NULL')
       ->setParameter('t', $team)
       ->setParameter('st', Invitation::STATUS_PENDING);

    $q = trim(mb_strtolower($q));
    if ($q !== '') {
        $qb->andWhere('LOWER(p.nickname) LIKE :q')
           ->setParameter('q', '%'.$q.'%');
    }

    return $qb->setMaxResults($limit)->getQuery()->getResult();
}

}
