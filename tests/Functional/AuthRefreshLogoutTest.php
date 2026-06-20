<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthRefreshLogoutTest extends WebTestCase
{
    public function testRefreshAndLogoutRevokeRefreshToken(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'refresh-user@example.com',
                'password' => 'password123',
                'displayName' => 'Refresh User',
                'role' => 'customer',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'refresh-user@example.com',
                'password' => 'password123',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $login = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('token', $login);
        self::assertArrayHasKey('refresh_token', $login);

        $client->request(
            'POST',
            '/api/auth/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => $login['refresh_token']], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $refreshed = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('token', $refreshed);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$refreshed['token']);
        $client->request(
            'POST',
            '/api/auth/logout',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['refresh_token' => $login['refresh_token']], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
    }
}
