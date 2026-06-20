<?php

declare(strict_types=1);

namespace App\Service\Cart;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Enum\ProductStatus;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CartService
{
    public function __construct(
        private CartRepository $carts,
        private ProductRepository $products,
        private EntityManagerInterface $em,
    ) {
    }

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $this->carts->findForUser($user);
        if ($cart !== null) {
            return $cart;
        }
        $cart = (new Cart())->setUser($user);
        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }

    public function addOrUpdateLine(User $user, int $productId, int $quantity): Cart
    {
        $product = $this->products->find($productId);
        if ($product === null || $product->getStatus() !== ProductStatus::Published) {
            throw new \InvalidArgumentException(sprintf('Product %d is not available for purchase.', $productId));
        }
        $cart = $this->getOrCreateCart($user);
        $existing = null;
        foreach ($cart->getItems() as $line) {
            if ($line->getProduct()?->getId() === $productId) {
                $existing = $line;
                break;
            }
        }
        if ($existing !== null) {
            $existing->setQuantity($existing->getQuantity() + $quantity);
        } else {
            $line = (new CartItem())
                ->setCart($cart)
                ->setProduct($product)
                ->setQuantity($quantity);
            $cart->addItem($line);
            $this->em->persist($line);
        }
        $this->em->flush();

        return $cart;
    }

    public function setLineQuantity(User $user, int $productId, int $quantity): Cart
    {
        $cart = $this->requireCart($user);
        foreach ($cart->getItems() as $line) {
            if ($line->getProduct()?->getId() === $productId) {
                $line->setQuantity($quantity);
                $this->em->flush();

                return $cart;
            }
        }
        throw new \InvalidArgumentException('Line not found in cart.');
    }

    public function removeLine(User $user, int $productId): Cart
    {
        $cart = $this->requireCart($user);
        foreach ($cart->getItems() as $line) {
            if ($line->getProduct()?->getId() === $productId) {
                $cart->removeItem($line);
                $this->em->remove($line);
                $this->em->flush();

                return $cart;
            }
        }
        throw new \InvalidArgumentException('Line not found in cart.');
    }

    public function clear(User $user): void
    {
        $cart = $this->carts->findForUser($user);
        if ($cart === null) {
            return;
        }
        foreach ($cart->getItems()->toArray() as $line) {
            $cart->removeItem($line);
            $this->em->remove($line);
        }
        $this->em->flush();
    }

    private function requireCart(User $user): Cart
    {
        $cart = $this->carts->findForUser($user);
        if ($cart === null) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        return $cart;
    }
}
