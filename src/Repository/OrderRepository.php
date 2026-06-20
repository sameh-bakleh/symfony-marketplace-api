<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Order> */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /** @return list<Order> */
    public function findByCustomerWithItems(User $customer): array
    {
        return $this->createQueryBuilder('o')
            ->select('o', 'i')
            ->leftJoin('o.items', 'i')->addSelect('i')
            ->andWhere('o.customer = :c')
            ->setParameter('c', $customer)
            ->orderBy('o.placedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
