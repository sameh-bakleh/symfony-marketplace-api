<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\Product;

final class ProductResource
{
    /** @return array<string, mixed> */
    public static function fromEntity(Product $p, bool $detailed = false): array
    {
        $images = [];
        foreach ($p->getImages() as $img) {
            $images[] = [
                'id' => $img->getId(),
                'path' => $img->getPath(),
                'sortOrder' => $img->getSortOrder(),
            ];
        }
        $data = [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'slug' => $p->getSlug(),
            'priceMinor' => $p->getPriceMinor(),
            'currency' => $p->getCurrency(),
            'status' => $p->getStatus()->value,
            'category' => $p->getCategory() ? [
                'id' => $p->getCategory()->getId(),
                'name' => $p->getCategory()->getName(),
                'slug' => $p->getCategory()->getSlug(),
            ] : null,
            'seller' => $p->getSeller() ? [
                'id' => $p->getSeller()->getId(),
                'displayName' => $p->getSeller()->getDisplayName(),
            ] : null,
            'images' => $images,
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
        if ($detailed) {
            $data['description'] = $p->getDescription();
        }

        return $data;
    }

    /** @param iterable<Product> $products */
    public static function collection(iterable $products, bool $detailed = false): array
    {
        $out = [];
        foreach ($products as $p) {
            $out[] = self::fromEntity($p, $detailed);
        }

        return $out;
    }
}
