<?php

declare(strict_types=1);

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CategoryRepository;

final readonly class ActiveCategoriesProvider implements ProviderInterface
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->categories->findBy(['isActive' => true], ['name' => 'ASC']);
    }
}
