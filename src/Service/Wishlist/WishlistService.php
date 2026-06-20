<?php

declare(strict_types=1);

namespace App\Service\Wishlist;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\WishlistItem;
use App\Repository\WishlistItemRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WishlistService
{
    public function __construct(
        private WishlistItemRepository $wishlist,
        private EntityManagerInterface $em,
    ) {
    }

    public function add(User $user, Product $product): void
    {
        if ($this->wishlist->findOneByUserAndProduct($user, $product) !== null) {
            return;
        }
        $w = (new WishlistItem())->setUser($user)->setProduct($product);
        $this->em->persist($w);
        $this->em->flush();
    }

    public function remove(User $user, Product $product): void
    {
        $row = $this->wishlist->findOneByUserAndProduct($user, $product);
        if ($row !== null) {
            $this->em->remove($row);
            $this->em->flush();
        }
    }
}
