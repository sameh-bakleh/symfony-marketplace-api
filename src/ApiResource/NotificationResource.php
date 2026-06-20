<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\MarketplaceNotification;

final class NotificationResource
{
    /** @return array<string, mixed> */
    public static function fromEntity(MarketplaceNotification $n): array
    {
        return [
            'id' => $n->getId(),
            'type' => $n->getType(),
            'title' => $n->getTitle(),
            'body' => $n->getBody(),
            'readAt' => $n->getReadAt()?->format(\DateTimeInterface::ATOM),
            'context' => $n->getContext(),
            'createdAt' => $n->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @param iterable<MarketplaceNotification> $items */
    public static function collection(iterable $items): array
    {
        $out = [];
        foreach ($items as $n) {
            $out[] = self::fromEntity($n);
        }

        return $out;
    }
}
