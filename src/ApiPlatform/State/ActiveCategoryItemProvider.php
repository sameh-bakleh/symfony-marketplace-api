<?php

declare(strict_types=1);

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CategoryRepository;

final readonly class ActiveCategoryItemProvider implements ProviderInterface
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $id = isset($uriVariables['id']) ? (int) $uriVariables['id'] : 0;
        if ($id < 1) {
            return null;
        }
        $category = $this->categories->find($id);
        if ($category === null || !$category->isActive()) {
            return null;
        }

        return $category;
    }
}
