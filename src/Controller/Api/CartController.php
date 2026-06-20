<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Request\CartLineRequest;
use App\Dto\Request\CartQuantityUpdateRequest;
use App\Entity\User;
use App\Service\Cart\CartService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Cart')]
#[Route('/api/cart')]
#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cart,
    ) {
    }

    #[Route('', name: 'api_cart_get', methods: ['GET'])]
    #[OA\Get(path: '/api/cart', summary: 'Get current cart', security: [['bearerAuth' => []]])]
    public function get(): JsonResponse
    {
        $cart = $this->cart->getOrCreateCart($this->user());

        return $this->json($this->serializeCart($cart));
    }

    #[Route('/items', name: 'api_cart_add_item', methods: ['POST'])]
    #[OA\Post(path: '/api/cart/items', summary: 'Add or update cart line', security: [['bearerAuth' => []]])]
    public function addItem(#[MapRequestPayload] CartLineRequest $dto): JsonResponse
    {
        try {
            $cart = $this->cart->addOrUpdateLine($this->user(), $dto->productId, $dto->quantity);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeCart($cart), Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'api_cart_patch_item', methods: ['PATCH'], requirements: ['productId' => '\d+'])]
    public function patchItem(int $productId, #[MapRequestPayload] CartQuantityUpdateRequest $dto): JsonResponse
    {
        try {
            $cart = $this->cart->setLineQuantity($this->user(), $productId, $dto->quantity);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeCart($cart));
    }

    #[Route('/items/{productId}', name: 'api_cart_delete_item', methods: ['DELETE'], requirements: ['productId' => '\d+'])]
    public function deleteItem(int $productId): JsonResponse
    {
        try {
            $cart = $this->cart->removeLine($this->user(), $productId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serializeCart($cart));
    }

    #[Route('', name: 'api_cart_clear', methods: ['DELETE'])]
    public function clear(): JsonResponse
    {
        $this->cart->clear($this->user());

        return $this->json(['items' => [], 'totalQuantity' => 0]);
    }

    private function user(): User
    {
        $u = $this->getUser();
        if (!$u instanceof User) {
            throw new \LogicException();
        }

        return $u;
    }

    private function serializeCart(\App\Entity\Cart $cart): array
    {
        $items = [];
        $totalQty = 0;
        foreach ($cart->getItems() as $line) {
            $p = $line->getProduct();
            $qty = $line->getQuantity();
            $totalQty += $qty;
            $items[] = [
                'productId' => $p?->getId(),
                'title' => $p?->getTitle(),
                'quantity' => $qty,
                'unitPriceMinor' => $p?->getPriceMinor(),
                'currency' => $p?->getCurrency(),
            ];
        }

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'totalQuantity' => $totalQty,
        ];
    }
}
