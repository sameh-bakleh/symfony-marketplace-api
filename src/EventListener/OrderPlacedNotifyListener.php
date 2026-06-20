<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\OrderItem;
use App\Entity\Product;
use App\Event\OrderPlacedEvent;
use App\Message\PersistNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: OrderPlacedEvent::class)]
final readonly class OrderPlacedNotifyListener
{
    public function __construct(
        private MessageBusInterface $bus,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(OrderPlacedEvent $event): void
    {
        $order = $event->order;
        $customer = $order->getCustomer();
        if ($customer !== null && $customer->getId() !== null) {
            $this->bus->dispatch(new PersistNotificationMessage(
                userId: $customer->getId(),
                type: 'order_placed_customer',
                title: 'Order received',
                body: sprintf('Your order #%d was placed successfully.', $order->getId() ?? 0),
                context: ['orderId' => $order->getId()],
            ));
        }

        $sellerIds = [];
        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItem || $item->getProductId() === null) {
                continue;
            }
            $product = $this->em->find(Product::class, $item->getProductId());
            $seller = $product?->getSeller();
            if ($seller !== null && $seller->getId() !== null) {
                $sellerIds[$seller->getId()] = true;
            }
        }

        foreach (array_keys($sellerIds) as $sellerUserId) {
            $this->bus->dispatch(new PersistNotificationMessage(
                userId: $sellerUserId,
                type: 'order_placed_seller',
                title: 'New order',
                body: sprintf('Order #%d includes your products.', $order->getId() ?? 0),
                context: ['orderId' => $order->getId()],
            ));
        }
    }
}
