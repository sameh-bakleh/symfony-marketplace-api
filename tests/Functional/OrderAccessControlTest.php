<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ProductStatus;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OrderAccessControlTest extends WebTestCase
{
    public function testCustomerCannotViewAnotherCustomersOrder(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('Cat')->setSlug('cat-ac')->setIsActive(true);
        $em->persist($cat);

        $seller = (new User())->setEmail('seller-ac@example.com')->setDisplayName('S')->setRoles([UserRole::Seller->value]);
        $seller->setPassword($hasher->hashPassword($seller, 'password123'));
        $alice = (new User())->setEmail('alice-ac@example.com')->setDisplayName('A')->setRoles([UserRole::Customer->value]);
        $alice->setPassword($hasher->hashPassword($alice, 'password123'));
        $bob = (new User())->setEmail('bob-ac@example.com')->setDisplayName('B')->setRoles([UserRole::Customer->value]);
        $bob->setPassword($hasher->hashPassword($bob, 'password123'));
        $em->persist($seller);
        $em->persist($alice);
        $em->persist($bob);
        $em->flush();

        $product = (new Product())
            ->setSeller($seller)
            ->setCategory($cat)
            ->setTitle('Thing')
            ->setSlug('thing-ac')
            ->setDescription('D')
            ->setPriceMinor(100)
            ->setCurrency('USD')
            ->setStatus(ProductStatus::Published);
        $em->persist($product);
        $em->flush();

        $order = (new Order())
            ->setCustomer($alice)
            ->setStatus(OrderStatus::Pending)
            ->setCurrency('USD')
            ->setTotalMinor(100);
        $line = (new OrderItem())
            ->setProductId($product->getId())
            ->setProductTitle($product->getTitle())
            ->setUnitPriceMinor(100)
            ->setQuantity(1);
        $order->addItem($line);
        $em->persist($line);
        $em->persist($order);
        $em->flush();
        $orderId = $order->getId();
        self::assertNotNull($orderId);

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'bob-ac@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $client->request('GET', '/api/orders/'.$orderId);
        self::assertResponseStatusCodeSame(403);
    }
}
