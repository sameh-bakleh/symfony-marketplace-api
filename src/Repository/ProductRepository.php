<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Enum\ProductStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function createPublishedListingQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'c', 'i', 's')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.images', 'i')->addSelect('i')
            ->leftJoin('p.seller', 's')->addSelect('s')
            ->andWhere('p.status = :published')
            ->setParameter('published', ProductStatus::Published);
    }

    public function findPublishedByIdWithRelations(int $id): ?Product
    {
        return $this->createPublishedListingQueryBuilder()
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublishedBySlugWithRelations(string $slug): ?Product
    {
        return $this->createPublishedListingQueryBuilder()
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
