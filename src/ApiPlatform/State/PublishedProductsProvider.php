<?php

declare(strict_types=1);

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;

final readonly class PublishedProductsProvider implements ProviderInterface
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->products->createPublishedListingQueryBuilder()
            ->setMaxResults(100)
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
