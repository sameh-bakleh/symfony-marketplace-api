<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CategoryPublicTest extends WebTestCase
{
    public function testPublicCategoryListAndDetail(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $category = (new Category())->setName('Electronics')->setSlug('electronics-pub')->setIsActive(true);
        $em->persist($category);
        $em->flush();
        $id = $category->getId();
        self::assertNotNull($id);

        $client->request('GET', '/api/categories');
        self::assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($list);

        $client->request('GET', '/api/categories/'.$id);
        self::assertResponseIsSuccessful();
        $one = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Electronics', $one['name']);
    }
}
