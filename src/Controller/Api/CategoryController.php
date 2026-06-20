<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\CategoryResource;
use App\Dto\Request\CategoryWriteRequest;
use App\Repository\CategoryRepository;
use App\Service\Catalog\CategoryService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Categories')]
#[Route('/api/categories')]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly CategoryRepository $categoryRepo,
    ) {
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->categories->listPublicTree());
    }

    #[Route('/{id}', name: 'api_categories_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function one(int $id): JsonResponse
    {
        $c = $this->categoryRepo->find($id);
        if ($c === null || !$c->isActive()) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(CategoryResource::fromEntity($c));
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] CategoryWriteRequest $dto): JsonResponse
    {
        $parent = $dto->parentId !== null ? $this->categoryRepo->find($dto->parentId) : null;
        if ($dto->parentId !== null && $parent === null) {
            return $this->json(['error' => 'Parent not found'], Response::HTTP_BAD_REQUEST);
        }
        $c = $this->categories->create($dto->name, $dto->description, $parent);

        return $this->json(CategoryResource::fromEntity($c), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, #[MapRequestPayload] CategoryWriteRequest $dto): JsonResponse
    {
        $c = $this->categoryRepo->find($id);
        if ($c === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        $parent = $dto->parentId !== null ? $this->categoryRepo->find($dto->parentId) : null;
        if ($dto->parentId !== null && $parent === null) {
            return $this->json(['error' => 'Parent not found'], Response::HTTP_BAD_REQUEST);
        }
        $this->categories->update($c, $dto->name, $dto->description, $dto->isActive, $parent);

        return $this->json(CategoryResource::fromEntity($c));
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $c = $this->categoryRepo->find($id);
        if ($c === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        try {
            $this->categories->delete($c);
        } catch (\Throwable) {
            return $this->json(['error' => 'Cannot delete category with products'], Response::HTTP_CONFLICT);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
