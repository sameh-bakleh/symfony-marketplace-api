<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\ProductResource;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductStatus;
use App\Repository\WishlistItemRepository;
use App\Service\Wishlist\WishlistService;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Wishlist')]
#[Route('/api/wishlist')]
#[IsGranted('ROLE_USER')]
final class WishlistController extends AbstractController
{
    public function __construct(
        private readonly WishlistService $wishlist,
        private readonly WishlistItemRepository $items,
    ) {
    }

    #[Route('', name: 'api_wishlist_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->user();
        $rows = $this->items->findForUserWithProducts($user);
        $products = [];
        foreach ($rows as $row) {
            $p = $row->getProduct();
            if ($p !== null && $p->getStatus() === ProductStatus::Published) {
                $products[] = ProductResource::fromEntity($p, false);
            }
        }

        return $this->json(['items' => $products]);
    }

    #[Route('/products/{product}', name: 'api_wishlist_add', methods: ['POST'], requirements: ['product' => '\d+'])]
    public function add(#[MapEntity(id: 'product')] Product $product): JsonResponse
    {
        if ($product->getStatus() !== ProductStatus::Published) {
            return $this->json(['error' => 'Product not available'], Response::HTTP_BAD_REQUEST);
        }
        $this->wishlist->add($this->user(), $product);

        return $this->json(['message' => 'Added'], Response::HTTP_CREATED);
    }

    #[Route('/products/{product}', name: 'api_wishlist_remove', methods: ['DELETE'], requirements: ['product' => '\d+'])]
    public function remove(#[MapEntity(id: 'product')] Product $product): JsonResponse
    {
        $this->wishlist->remove($this->user(), $product);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function user(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User) {
            throw new \LogicException();
        }

        return $u;
    }
}
