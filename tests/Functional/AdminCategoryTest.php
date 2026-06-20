<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminCategoryTest extends WebTestCase
{
    public function testAdminCanCreateCategory(): void
    {
        $client = static::createClient();
        $token = $this->loginAs($client, 'admin-cat@example.com', [UserRole::Admin->value]);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
        $client->request(
            'POST',
            '/api/categories',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'name' => 'Admin Created',
                'description' => 'From test',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $body = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Admin Created', $body['name']);
    }

    public function testCustomerCannotCreateCategory(): void
    {
        $client = static::createClient();
        $token = $this->loginAs($client, 'customer-cat@example.com', [UserRole::Customer->value]);

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
        $client->request(
            'POST',
            '/api/categories',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['name' => 'Forbidden', 'description' => 'Nope'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @param list<string> $roles
     */
    private function loginAs(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $email, array $roles): string
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = (new User())->setEmail($email)->setDisplayName('Test User')->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        $em->persist($user);
        $em->flush();

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
    }
}
