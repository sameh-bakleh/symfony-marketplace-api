<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\ProductResource;
use App\Dto\Request\CreateProductRequest;
use App\Dto\Request\ProductWriteRequest;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductStatus;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Security\Voter\ProductVoter;
use App\Service\Catalog\ProductService;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Products')]
#[Route('/api')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductRepository $productRepo,
        private readonly CategoryRepository $categoryRepo,
    ) {
    }

    #[Route('/products', name: 'api_products_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $query = [
            'page' => (int) $request->query->get('page', 1),
            'perPage' => (int) $request->query->get('perPage', 20),
            'categoryId' => $request->query->get('categoryId') ? (int) $request->query->get('categoryId') : null,
            'sellerId' => $request->query->get('sellerId') ? (int) $request->query->get('sellerId') : null,
            'search' => $request->query->get('search'),
            'minPrice' => $request->query->get('minPrice') !== null ? (int) $request->query->get('minPrice') : null,
            'maxPrice' => $request->query->get('maxPrice') !== null ? (int) $request->query->get('maxPrice') : null,
            'sort' => $request->query->get('sort', 'newest'),
        ];

        return $this->json($this->products->listPublished($query));
    }

    #[Route('/products/{id}', name: 'api_products_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function one(int $id): JsonResponse
    {
        $p = $this->productRepo->findPublishedByIdWithRelations($id);
        if ($p === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(ProductResource::fromEntity($p, true));
    }

    #[Route('/products/slug/{slug}', name: 'api_products_by_slug', methods: ['GET'])]
    public function bySlug(string $slug): JsonResponse
    {
        $p = $this->productRepo->findPublishedBySlugWithRelations($slug);
        if ($p === null) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(ProductResource::fromEntity($p, true));
    }

    #[Route('/seller/products', name: 'api_seller_products_create', methods: ['POST'])]
    #[IsGranted('ROLE_SELLER')]
    public function create(#[MapRequestPayload] CreateProductRequest $dto): JsonResponse
    {
        $user = $this->currentUser();
        $category = $this->categoryRepo->find($dto->categoryId);
        if ($category === null || !$category->isActive()) {
            return $this->json(['error' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }
        $status = ProductStatus::from($dto->status);
        $p = $this->products->create(
            $user,
            $category,
            $dto->title,
            $dto->description,
            $dto->priceMinor,
            $dto->currency,
            $status,
            [],
        );

        return $this->json(ProductResource::fromEntity($p, true), Response::HTTP_CREATED);
    }

    #[Route('/seller/products/{product}', name: 'api_seller_products_update', methods: ['PATCH'], requirements: ['product' => '\d+'])]
    #[IsGranted(ProductVoter::EDIT, 'product')]
    public function update(#[MapEntity(id: 'product')] Product $product, #[MapRequestPayload] ProductWriteRequest $dto): JsonResponse
    {
        $category = $dto->categoryId !== null ? $this->categoryRepo->find($dto->categoryId) : null;
        if ($dto->categoryId !== null && ($category === null || !$category->isActive())) {
            return $this->json(['error' => 'Invalid category'], Response::HTTP_BAD_REQUEST);
        }
        $status = $dto->status !== null ? ProductStatus::from($dto->status) : null;
        $this->products->update(
            $product,
            $category,
            $dto->title,
            $dto->description,
            $dto->priceMinor,
            $dto->currency,
            $status,
        );

        return $this->json(ProductResource::fromEntity($product, true));
    }

    #[Route('/seller/products/{product}', name: 'api_seller_products_delete', methods: ['DELETE'], requirements: ['product' => '\d+'])]
    #[IsGranted(ProductVoter::EDIT, 'product')]
    public function delete(Product $product): JsonResponse
    {
        $this->products->delete($product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/seller/products/{product}/images', name: 'api_seller_products_images', methods: ['POST'], requirements: ['product' => '\d+'])]
    #[IsGranted(ProductVoter::EDIT, 'product')]
    public function uploadImages(#[MapEntity(id: 'product')] Product $product, Request $request): JsonResponse
    {
        /** @var UploadedFile[] $files */
        $files = $request->files->all('images') ?: [];
        if ($files === []) {
            return $this->json(['error' => 'No images uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $list = \is_array($files) ? $files : [$files];
        $this->products->addImages($product, $list);

        return $this->json(ProductResource::fromEntity($product, true));
    }

    private function currentUser(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User) {
            throw new \LogicException();
        }

        return $u;
    }
}
