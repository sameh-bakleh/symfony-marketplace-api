<?php

declare(strict_types=1);

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;

final readonly class PublishedProductItemProvider implements ProviderInterface
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : 0;
        if ($id < 1) {
            return null;
        }

        return $this->products->findPublishedByIdWithRelations($id);
    }
}
