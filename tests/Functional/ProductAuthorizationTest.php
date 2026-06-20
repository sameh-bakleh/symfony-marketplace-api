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

final class ProductAuthorizationTest extends WebTestCase
{
    public function testSellerCannotEditOtherSellerProduct(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $cat = (new Category())->setName('C')->setSlug('c')->setIsActive(true);
        $em->persist($cat);

        $a = (new User())->setEmail('a@example.com')->setDisplayName('A')->setRoles([UserRole::Seller->value]);
        $a->setPassword($hasher->hashPassword($a, 'password123'));
        $b = (new User())->setEmail('b@example.com')->setDisplayName('B')->setRoles([UserRole::Seller->value]);
        $b->setPassword($hasher->hashPassword($b, 'password123'));
        $em->persist($a);
        $em->persist($b);
        $em->flush();

        $p = (new Product())
            ->setSeller($a)
            ->setCategory($cat)
            ->setTitle('Widget')
            ->setSlug('widget')
            ->setDescription('D')
            ->setPriceMinor(1000)
            ->setCurrency('USD')
            ->setStatus(ProductStatus::Published);
        $em->persist($p);
        $em->flush();

        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'b@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR)
        );
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        $client->request(
            'PATCH',
            '/api/seller/products/'.$p->getId(),
            server: [
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Hacked'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(403);
    }
}
