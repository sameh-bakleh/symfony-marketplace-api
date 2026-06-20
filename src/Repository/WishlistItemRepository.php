<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\WishlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WishlistItem>
 */
class WishlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WishlistItem::class);
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?WishlistItem
    {
        return $this->findOneBy(['user' => $user, 'product' => $product]);
    }

    /** @return list<WishlistItem> */
    public function findForUserWithProducts(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->select('w', 'p', 'i', 'c')
            ->leftJoin('w.product', 'p')->addSelect('p')
            ->leftJoin('p.images', 'i')->addSelect('i')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
