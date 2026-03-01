<?php

namespace App\Repository;

use App\Entity\ShopProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopProduct>
 */
class ShopProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopProduct::class);
    }

    /**
     * Trouve des produits par nom similaire (pour les recommandations AI)
     */
    public function findByNameLike(string $name, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :name')
            ->andWhere('p.isActive = :active')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('active', true)
            ->orderBy('p.price', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des produits par type
     */
    public function findByType(string $type, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits les plus populaires
     */
    public function findPopularProducts(int $limit = 8): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.price', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de produits avec filtres
     */
    public function searchWithFilters(?string $query = null, ?string $type = null, ?string $game = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        if ($query) {
            $qb->andWhere('p.name LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($game) {
            $qb->andWhere('p.game = :game')
               ->setParameter('game', $game);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function searchByNameAndType(string $name, string $type = ''): array
    {
        $qb = $this->createQueryBuilder('p');
        
        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }
        
        if ($name) {
            $qb->andWhere('p.name LIKE :name')
               ->setParameter('name', '%' . $name . '%');
        }
        
        return $qb->getQuery()
                  ->getResult();
    }

    /**
     * Trouve des produits avec leurs images (évite N+1)
     */
    public function findWithImages(int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('p', 'i')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des produits par type avec images (évite N+1)
     */
    public function findByTypeWithImages(string $type, int $limit = 20, ?string $orderBy = 'price', ?string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('p', 'i')
            ->where('p.type = :type')
            ->andWhere('p.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true);

        // Ajout du tri dynamique
        if ($orderBy && in_array($orderBy, ['price', 'createdAt', 'name'])) {
            $qb->orderBy('p.' . $orderBy, $order === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('p.price', 'ASC');
        }

        return $qb->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avec filtres et images (évite N+1)
     */
    public function searchWithFiltersAndImages(?string $query = null, ?string $type = null, ?string $game = null, int $limit = 50, ?string $orderBy = null, ?string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('p', 'i')
            ->where('p.isActive = :active')
            ->setParameter('active', true);

        if ($query) {
            $qb->andWhere('p.name LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($game) {
            $qb->andWhere('p.game = :game')
               ->setParameter('game', $game);
        }

        // Ajout du tri dynamique
        if ($orderBy && in_array($orderBy, ['price', 'createdAt', 'name'])) {
            $qb->orderBy('p.' . $orderBy, $order === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }
}
