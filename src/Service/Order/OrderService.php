<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ProductStatus;
use App\Event\OrderPlacedEvent;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class OrderService
{
    public function __construct(
        private ProductRepository $products,
        private EntityManagerInterface $em,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @param list<array{productId: int, quantity: int}> $lines
     */
    public function createFromLines(User $customer, array $lines): Order
    {
        $order = (new Order())
            ->setCustomer($customer)
            ->setStatus(OrderStatus::Pending)
            ->setCurrency('USD')
            ->setTotalMinor(0);

        $total = 0;
        $currency = null;
        foreach ($lines as $line) {
            $product = $this->products->find($line['productId']);
            if ($product === null || $product->getStatus() !== ProductStatus::Published) {
                throw new \InvalidArgumentException(sprintf('Product %d is not available.', $line['productId']));
            }
            $c = $product->getCurrency();
            if ($currency === null) {
                $currency = $c;
            } elseif ($currency !== $c) {
                throw new \InvalidArgumentException('Mixed currencies in one order are not supported.');
            }
            $qty = $line['quantity'];
            $lineTotal = $product->getPriceMinor() * $qty;
            $total += $lineTotal;
            $item = (new OrderItem())
                ->setProductId($product->getId())
                ->setProductTitle($product->getTitle())
                ->setUnitPriceMinor($product->getPriceMinor())
                ->setQuantity($qty);
            $order->addItem($item);
            $this->em->persist($item);
        }
        $order->setCurrency($currency ?? 'USD')->setTotalMinor($total);
        $this->em->persist($order);
        $this->em->flush();

        $this->events->dispatch(new OrderPlacedEvent($order));

        return $order;
    }

    /**
     * Builds an order from the customer's cart lines, then removes those lines.
     *
     * @throws \InvalidArgumentException
     */
    public function createFromCart(User $customer, Cart $cart): Order
    {
        if ($cart->getUser()?->getId() !== $customer->getId()) {
            throw new \InvalidArgumentException('Cart does not belong to this user.');
        }
        $lines = [];
        foreach ($cart->getItems() as $line) {
            $pid = $line->getProduct()?->getId();
            if ($pid === null) {
                continue;
            }
            $lines[] = ['productId' => $pid, 'quantity' => $line->getQuantity()];
        }
        if ($lines === []) {
            throw new \InvalidArgumentException('Cart is empty.');
        }
        $order = $this->createFromLines($customer, $lines);
        foreach ($cart->getItems()->toArray() as $item) {
            $cart->removeItem($item);
            $this->em->remove($item);
        }
        $this->em->flush();

        return $order;
    }

    public function updateStatus(Order $order, OrderStatus $newStatus): void
    {
        $order->setStatus($newStatus);
        $this->em->flush();
    }
}
