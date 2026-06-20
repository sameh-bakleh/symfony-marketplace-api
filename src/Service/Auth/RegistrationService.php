<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\SellerProfile;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegistrationService
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(
        string $email,
        string $plainPassword,
        string $displayName,
        string $role,
        ?string $phone,
        ?string $storeName,
    ): User {
        if ($this->users->findOneBy(['email' => $email]) !== null) {
            throw new ConflictHttpException('Email already registered.');
        }
        if ($role === 'seller' && ($storeName === null || $storeName === '')) {
            throw new \InvalidArgumentException('storeName is required for seller registration.');
        }

        $roles = $role === 'seller'
            ? [UserRole::Seller->value, UserRole::Customer->value]
            : [UserRole::Customer->value];

        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($displayName)
            ->setPhone($phone)
            ->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        if ($role === 'seller') {
            $user->setSellerProfile(
                (new SellerProfile())->setStoreName((string) $storeName)
            );
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
