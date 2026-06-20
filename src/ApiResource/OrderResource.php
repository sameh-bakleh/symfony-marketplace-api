<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\Order;

final class OrderResource
{
    /** @return array<string, mixed> */
    public static function fromEntity(Order $o): array
    {
        $items = [];
        foreach ($o->getItems() as $i) {
            $items[] = [
                'id' => $i->getId(),
                'productId' => $i->getProductId(),
                'productTitle' => $i->getProductTitle(),
                'unitPriceMinor' => $i->getUnitPriceMinor(),
                'quantity' => $i->getQuantity(),
            ];
        }

        return [
            'id' => $o->getId(),
            'status' => $o->getStatus()->value,
            'currency' => $o->getCurrency(),
            'totalMinor' => $o->getTotalMinor(),
            'placedAt' => $o->getPlacedAt()->format(\DateTimeInterface::ATOM),
            'items' => $items,
        ];
    }

    /** @param iterable<Order> $orders */
    public static function collection(iterable $orders): array
    {
        $out = [];
        foreach ($orders as $o) {
            $out[] = self::fromEntity($o);
        }

        return $out;
    }
}
