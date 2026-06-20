<?php

declare(strict_types=1);

namespace App\Service\Cache;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class ListingCacheInvalidator
{
    public function __construct(private TagAwareCacheInterface $cache)
    {
    }

    public function bumpCatalogCaches(): void
    {
        $this->cache->invalidateTags(['category_tree', 'product_listings']);
    }
}
