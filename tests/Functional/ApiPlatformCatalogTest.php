<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiPlatformCatalogTest extends WebTestCase
{
    public function testPublicJsonLdCategories(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist((new Category())->setName('API Platform Cat')->setSlug('ap-cat')->setIsActive(true));
        $em->flush();

        $client->request(
            'GET',
            '/api/platform/categories.jsonld',
            server: ['HTTP_ACCEPT' => 'application/ld+json']
        );
        self::assertResponseIsSuccessful();
        $raw = $client->getResponse()->getContent();
        self::assertIsString($raw);
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('member', $data);
        self::assertStringContainsString('ap-cat', $raw);
    }
}
