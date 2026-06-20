<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\NotificationResource;
use App\Entity\MarketplaceNotification;
use App\Entity\User;
use App\Repository\MarketplaceNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Notifications')]
#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly MarketplaceNotificationRepository $notifications,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = $this->notifications->findRecentForUser($this->user());

        return $this->json(NotificationResource::collection($items));
    }

    #[Route('/{notification}/read', name: 'api_notifications_read', methods: ['PATCH'], requirements: ['notification' => '\d+'])]
    public function markRead(#[MapEntity(id: 'notification')] MarketplaceNotification $notification): JsonResponse
    {
        if ($notification->getUser()?->getId() !== $this->user()->getId()) {
            return $this->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }
        $notification->setReadAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json(NotificationResource::fromEntity($notification));
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
