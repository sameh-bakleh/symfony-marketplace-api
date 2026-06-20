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

final class WishlistTest extends WebTestCase
{
    public function testAddProductToWishlistAndList(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('Wish Cat')->setSlug('wish-cat')->setIsActive(true);
        $em->persist($cat);

        $seller = (new User())->setEmail('seller-wish@example.com')->setDisplayName('S')->setRoles([UserRole::Seller->value]);
        $seller->setPassword($hasher->hashPassword($seller, 'password123'));
        $buyer = (new User())->setEmail('buyer-wish@example.com')->setDisplayName('B')->setRoles([UserRole::Customer->value]);
        $buyer->setPassword($hasher->hashPassword($buyer, 'password123'));
        $em->persist($seller);
        $em->persist($buyer);
        $em->flush();

        $product = (new Product())
            ->setSeller($seller)
            ->setCategory($cat)
            ->setTitle('Wish Product')
            ->setSlug('wish-product')
            ->setDescription('D')
            ->setPriceMinor(999)
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
            content: json_encode(['email' => 'buyer-wish@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $client->request('POST', '/api/wishlist/products/'.$pid);
        self::assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/wishlist');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $data['items']);
        self::assertSame('Wish Product', $data['items'][0]['title']);
    }
}
