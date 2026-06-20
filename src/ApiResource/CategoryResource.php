<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\Category;

final class CategoryResource
{
    /** @return array<string, mixed> */
    public static function fromEntity(Category $c): array
    {
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
            'description' => $c->getDescription(),
            'isActive' => $c->isActive(),
            'parentId' => $c->getParent()?->getId(),
        ];
    }

    /** @param iterable<Category> $categories */
    public static function collection(iterable $categories): array
    {
        $out = [];
        foreach ($categories as $c) {
            $out[] = self::fromEntity($c);
        }

        return $out;
    }
}
