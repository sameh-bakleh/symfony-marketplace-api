<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiResource\UserProfileResource;
use App\Dto\Request\LogoutRequest;
use App\Dto\Request\RegisterRequest;
use App\Service\Auth\RegistrationService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Authentication')]
final class AuthController extends AbstractController
{
    public function __construct(private readonly RegistrationService $registration)
    {
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register customer or seller',
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 409, description: 'Email taken'),
        ]
    )]
    public function register(#[MapRequestPayload] RegisterRequest $dto): JsonResponse
    {
        try {
            $user = $this->registration->register(
                $dto->email,
                $dto->password,
                $dto->displayName,
                $dto->role,
                $dto->phone,
                $dto->storeName,
            );
        } catch (ConflictHttpException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            ['id' => $user->getId(), 'email' => $user->getEmail()],
            Response::HTTP_CREATED
        );
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke refresh token',
        security: [['bearerAuth' => []]]
    )]
    public function logout(
        #[MapRequestPayload] LogoutRequest $dto,
        RefreshTokenManagerInterface $refreshTokens,
    ): JsonResponse {
        $token = $refreshTokens->get($dto->refresh_token);
        if ($token !== null) {
            $refreshTokens->delete($token);
        }

        return $this->json(['message' => 'Logged out.']);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[OA\Get(path: '/api/me', security: [['bearerAuth' => []]])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!is_object($user) || !method_exists($user, 'getId')) {
            throw new \LogicException('No authenticated user.');
        }
        /** @var \App\Entity\User $user */

        return $this->json(UserProfileResource::fromEntity($user));
    }
}
