<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\UserProfileResource;
use App\Dto\Request\UpdateProfileRequest;
use App\Dto\Request\UpdateSellerProfileRequest;
use App\Entity\User;
use App\Service\Profile\ProfileService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Profiles')]
#[Route('/api/profile')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    public function __construct(private readonly ProfileService $profiles)
    {
    }

    #[Route('', name: 'api_profile_patch', methods: ['PATCH'])]
    #[OA\Patch(security: [['bearerAuth' => []]])]
    public function update(#[MapRequestPayload] UpdateProfileRequest $dto): JsonResponse
    {
        $user = $this->currentUser();
        $this->profiles->updateUserProfile($user, $dto->displayName, $dto->phone);

        return $this->json(UserProfileResource::fromEntity($user));
    }

    #[Route('/seller', name: 'api_profile_seller_patch', methods: ['PATCH'])]
    #[OA\Patch(security: [['bearerAuth' => []]])]
    #[IsGranted('ROLE_SELLER')]
    public function updateSeller(#[MapRequestPayload] UpdateSellerProfileRequest $dto): JsonResponse
    {
        $user = $this->currentUser();
        try {
            $this->profiles->updateSellerProfile($user, $dto->storeName, $dto->bio);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(UserProfileResource::fromEntity($user));
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
