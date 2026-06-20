<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductListingTest extends WebTestCase
{
    public function testPublicListing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products');
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('total', $data);
    }
}
