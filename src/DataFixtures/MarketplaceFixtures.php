<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\SellerProfile;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ProductStatus;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MarketplaceFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $plain = 'DemoPass2026!';

        $admin = (new User())
            ->setEmail('admin@demo.marketplace')
            ->setDisplayName('Demo Admin')
            ->setRoles([UserRole::Admin->value]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $plain));
        $manager->persist($admin);

        $sellerUser = (new User())
            ->setEmail('seller@demo.marketplace')
            ->setDisplayName('Demo Seller')
            ->setRoles([UserRole::Seller->value]);
        $sellerUser->setPassword($this->passwordHasher->hashPassword($sellerUser, $plain));
        $seller = (new SellerProfile())
            ->setStoreName('Demo Crafts')
            ->setBio('Fixture seller for portfolio demos.');
        $sellerUser->setSellerProfile($seller);
        $manager->persist($sellerUser);

        $customer = (new User())
            ->setEmail('customer@demo.marketplace')
            ->setDisplayName('Demo Customer')
            ->setRoles([UserRole::Customer->value]);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $plain));
        $manager->persist($customer);

        $electronics = (new Category())
            ->setName('Electronics')
            ->setSlug('electronics')
            ->setDescription('Demo category')
            ->setIsActive(true);
        $manager->persist($electronics);

        $book = (new Product())
            ->setSeller($sellerUser)
            ->setCategory($electronics)
            ->setTitle('Demo paperback')
            ->setSlug('demo-paperback')
            ->setDescription('A fixture product for API tests.')
            ->setPriceMinor(1299)
            ->setCurrency('EUR')
            ->setStatus(ProductStatus::Published);
        $manager->persist($book);

        $gadget = (new Product())
            ->setSeller($sellerUser)
            ->setCategory($electronics)
            ->setTitle('Demo gadget')
            ->setSlug('demo-gadget')
            ->setDescription('Second fixture product.')
            ->setPriceMinor(4999)
            ->setCurrency('EUR')
            ->setStatus(ProductStatus::Published);
        $manager->persist($gadget);

        $manager->flush();

        $order = (new Order())
            ->setCustomer($customer)
            ->setStatus(OrderStatus::Pending)
            ->setCurrency('EUR')
            ->setTotalMinor(1299);
        $line = (new OrderItem())
            ->setProductId($book->getId())
            ->setProductTitle($book->getTitle())
            ->setUnitPriceMinor($book->getPriceMinor())
            ->setQuantity(1);
        $order->addItem($line);
        $manager->persist($line);
        $manager->persist($order);

        $manager->flush();
    }
}
