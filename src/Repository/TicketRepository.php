<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * Builds a filtered query for admin ticket list.
     */
    public function buildFilteredQuery(?string $search = null, ?string $status = null, ?int $categoryId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.submitter', 'u')
            ->leftJoin('t.category', 'c')
            ->addSelect('u', 'c')
            ->orderBy('t.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('t.subject LIKE :search OR t.description LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($status) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }
        if ($categoryId) {
            $qb->andWhere('c.id = :catId')
                ->setParameter('catId', $categoryId);
        }

        return $qb;
    }

    /**
     * Builds a query for a specific user's tickets.
     */
    public function buildUserTicketsQuery(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->andWhere('t.submitter = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.createdAt', 'DESC');
    }

    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t.id) as cnt')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($result as $row) {
            $out[$row['status']] = (int)$row['cnt'];
        }
        return $out;
    }

    public function countByCategory(): array
    {
        return $this->createQueryBuilder('t')
            ->select('c.name as category, COUNT(t.id) as cnt')
            ->leftJoin('t.category', 'c')
            ->groupBy('c.name')
            ->orderBy('cnt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns ticket creation trends for the last N days.
     */
    public function getTicketTrends(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        $result = $this->createQueryBuilder('t')
            ->select("SUBSTRING(t.createdAt, 1, 10) as day, COUNT(t.id) as cnt")
            ->andWhere('t.createdAt >= :since')
            ->setParameter('since', $since)
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getResult();

        // Fill gaps with 0
        $data = [];
        $current = new \DateTime("-{$days} days");
        $end = new \DateTime('now');
        while ($current <= $end) {
            $data[$current->format('Y-m-d')] = 0;
            $current->modify('+1 day');
        }
        foreach ($result as $row) {
            $data[$row['day']] = (int)$row['cnt'];
        }
        return $data;
    }

    public function getTopCategory(): ?string
    {
        $result = $this->createQueryBuilder('t')
            ->select('c.name as category, COUNT(t.id) as cnt')
            ->leftJoin('t.category', 'c')
            ->groupBy('c.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result ? $result['category'] : 'N/A';
    }
}