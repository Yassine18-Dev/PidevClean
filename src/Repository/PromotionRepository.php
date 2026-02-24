<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function findActivePromotions(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->where('p.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findApplicablePromotionsForProduct(int $productId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.products', 'prod')
            ->where('prod.id = :productId')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.startDate <= :now')
            ->andWhere('p.endDate >= :now')
            ->setParameter('productId', $productId)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.value', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
