<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterValidationTest extends WebTestCase
{
    public function testRegisterRejectsInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'not-an-email',
                'password' => 'password123',
                'displayName' => 'Bad',
                'role' => 'customer',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(422);
    }

    public function testSellerRegisterRequiresStoreName(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'seller-missing@example.com',
                'password' => 'password123',
                'displayName' => 'Seller',
                'role' => 'seller',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(400);
    }
}
