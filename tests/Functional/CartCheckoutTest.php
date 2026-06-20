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

final class CartCheckoutTest extends WebTestCase
{
    public function testCheckoutCartCreatesOrderAndClearsCart(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('Cat')->setSlug('cat-cc')->setIsActive(true);
        $em->persist($cat);

        $seller = (new User())->setEmail('seller-cc@example.com')->setDisplayName('S')->setRoles([UserRole::Seller->value]);
        $seller->setPassword($hasher->hashPassword($seller, 'password123'));
        $buyer = (new User())->setEmail('buyer-cc@example.com')->setDisplayName('B')->setRoles([UserRole::Customer->value]);
        $buyer->setPassword($hasher->hashPassword($buyer, 'password123'));
        $em->persist($seller);
        $em->persist($buyer);
        $em->flush();

        $product = (new Product())
            ->setSeller($seller)
            ->setCategory($cat)
            ->setTitle('Cart item')
            ->setSlug('cart-item')
            ->setDescription('D')
            ->setPriceMinor(250)
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
            content: json_encode(['email' => 'buyer-cc@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $client->request(
            'POST',
            '/api/cart/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['productId' => $pid, 'quantity' => 2], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/orders/checkout-cart');
        self::assertResponseStatusCodeSame(201);
        $order = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(500, $order['totalMinor']);

        $client->request('GET', '/api/cart');
        self::assertResponseIsSuccessful();
        $cart = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $cart['totalQuantity']);
    }
}
