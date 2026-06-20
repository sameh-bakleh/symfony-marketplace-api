<?php

declare(strict_types=1);

namespace App\Service\Cache;

final class ListingCacheKeys
{
    public const CATEGORIES = 'marketplace.categories.v1';

    public static function productsList(array $filters): string
    {
        ksort($filters);

        return 'marketplace.products.v1.'.md5(json_encode($filters, JSON_THROW_ON_ERROR));
    }
}
