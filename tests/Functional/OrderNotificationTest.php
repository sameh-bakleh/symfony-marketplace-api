<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductStatus;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OrderNotificationTest extends WebTestCase
{
    public function testOrderPlacementCreatesNotificationsForBuyerAndSeller(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('Cat')->setSlug('cat-notify')->setIsActive(true);
        $em->persist($cat);

        $seller = (new User())->setEmail('seller-notify@example.com')->setDisplayName('S')->setRoles([UserRole::Seller->value]);
        $seller->setPassword($hasher->hashPassword($seller, 'password123'));
        $buyer = (new User())->setEmail('buyer-notify@example.com')->setDisplayName('B')->setRoles([UserRole::Customer->value]);
        $buyer->setPassword($hasher->hashPassword($buyer, 'password123'));
        $em->persist($seller);
        $em->persist($buyer);
        $em->flush();

        $product = (new Product())
            ->setSeller($seller)
            ->setCategory($cat)
            ->setTitle('Notify item')
            ->setSlug('notify-item')
            ->setDescription('D')
            ->setPriceMinor(100)
            ->setCurrency('USD')
            ->setStatus(ProductStatus::Published);
        $em->persist($product);
        $em->flush();
        $pid = $product->getId();
        self::assertNotNull($pid);

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'buyer-notify@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $buyerToken = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$buyerToken);
        $client->request(
            'POST',
            '/api/orders',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['items' => [['productId' => $pid, 'quantity' => 1]]], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/notifications');
        self::assertResponseIsSuccessful();
        $buyerNotes = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($buyerNotes);
        self::assertSame('order_placed_customer', $buyerNotes[0]['type']);

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'seller-notify@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $sellerToken = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$sellerToken);
        $client->request('GET', '/api/notifications');
        self::assertResponseIsSuccessful();
        $sellerNotes = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($sellerNotes);
        self::assertSame('order_placed_seller', $sellerNotes[0]['type']);

        $noteId = $sellerNotes[0]['id'];
        $client->request('PATCH', '/api/notifications/'.$noteId.'/read');
        self::assertResponseIsSuccessful();
        $read = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotNull($read['readAt']);
    }
}
