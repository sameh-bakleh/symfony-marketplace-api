<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\OrderResource;
use App\Dto\Request\CreateOrderRequest;
use App\Dto\Request\OrderStatusUpdateRequest;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Security\Voter\OrderVoter;
use App\Service\Cart\CartService;
use App\Service\Order\OrderService;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Orders')]
#[Route('/api/orders')]
#[IsGranted('ROLE_USER')]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly OrderRepository $orderRepo,
        private readonly CartService $cart,
    ) {
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateOrderRequest $dto): JsonResponse
    {
        $lines = [];
        foreach ($dto->items as $row) {
            if (!isset($row['productId'], $row['quantity']) || !\is_int($row['productId']) || !\is_int($row['quantity'])) {
                return $this->json(['error' => 'Each item needs productId and quantity integers'], Response::HTTP_BAD_REQUEST);
            }
            $lines[] = ['productId' => $row['productId'], 'quantity' => $row['quantity']];
        }
        try {
            $order = $this->orders->createFromLines($this->user(), $lines);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(OrderResource::fromEntity($order), Response::HTTP_CREATED);
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = $this->orderRepo->findByCustomerWithItems($this->user());

        return $this->json(OrderResource::collection($list));
    }

    #[Route('/checkout-cart', name: 'api_orders_checkout_cart', methods: ['POST'])]
    #[OA\Post(path: '/api/orders/checkout-cart', summary: 'Checkout cart into order', security: [['bearerAuth' => []]])]
    public function checkoutCart(): JsonResponse
    {
        $cart = $this->cart->getOrCreateCart($this->user());
        try {
            $order = $this->orders->createFromCart($this->user(), $cart);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(OrderResource::fromEntity($order), Response::HTTP_CREATED);
    }

    #[Route('/{order}', name: 'api_orders_one', methods: ['GET'], requirements: ['order' => '\d+'])]
    #[IsGranted(OrderVoter::VIEW, subject: 'order')]
    public function one(#[MapEntity(id: 'order')] Order $order): JsonResponse
    {
        return $this->json(OrderResource::fromEntity($order));
    }

    #[Route('/{order}/status', name: 'api_orders_status', methods: ['PATCH'], requirements: ['order' => '\d+'])]
    #[IsGranted(OrderVoter::UPDATE_STATUS, subject: 'order')]
    public function updateStatus(#[MapEntity(id: 'order')] Order $order, #[MapRequestPayload] OrderStatusUpdateRequest $dto): JsonResponse
    {
        $this->orders->updateStatus($order, OrderStatus::from($dto->status));

        return $this->json(OrderResource::fromEntity($order));
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
