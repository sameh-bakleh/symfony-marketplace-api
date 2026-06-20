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

final class CartCrudTest extends WebTestCase
{
    public function testCartAddUpdateRemoveAndClear(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('Cart Cat')->setSlug('cart-cat')->setIsActive(true);
        $em->persist($cat);

        $seller = (new User())->setEmail('seller-cart@example.com')->setDisplayName('S')->setRoles([UserRole::Seller->value]);
        $seller->setPassword($hasher->hashPassword($seller, 'password123'));
        $buyer = (new User())->setEmail('buyer-cart@example.com')->setDisplayName('B')->setRoles([UserRole::Customer->value]);
        $buyer->setPassword($hasher->hashPassword($buyer, 'password123'));
        $em->persist($seller);
        $em->persist($buyer);
        $em->flush();

        $product = (new Product())
            ->setSeller($seller)
            ->setCategory($cat)
            ->setTitle('Cart Product')
            ->setSlug('cart-product')
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
            content: json_encode(['email' => 'buyer-cart@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $client->request(
            'POST',
            '/api/cart/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['productId' => $pid, 'quantity' => 1], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $cart = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $cart['totalQuantity']);

        $client->request(
            'PATCH',
            '/api/cart/items/'.$pid,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['quantity' => 3], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $cart = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(3, $cart['totalQuantity']);

        $client->request('DELETE', '/api/cart/items/'.$pid);
        self::assertResponseIsSuccessful();
        $cart = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $cart['totalQuantity']);

        $client->request(
            'POST',
            '/api/cart/items',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['productId' => $pid, 'quantity' => 2], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();

        $client->request('DELETE', '/api/cart');
        self::assertResponseIsSuccessful();
        $cleared = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $cleared['totalQuantity']);
    }
}
