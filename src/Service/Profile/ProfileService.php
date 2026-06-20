<?php

declare(strict_types=1);

namespace App\Service\Profile;

use App\Entity\SellerProfile;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProfileService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function updateUserProfile(User $user, ?string $displayName, ?string $phone): void
    {
        if ($displayName !== null) {
            $user->setDisplayName($displayName);
        }
        if ($phone !== null) {
            $user->setPhone($phone);
        }
        $this->em->flush();
    }

    public function updateSellerProfile(User $user, ?string $storeName, ?string $bio): void
    {
        if (!$user->hasRole(UserRole::Seller->value)) {
            throw new \InvalidArgumentException('User is not a seller.');
        }
        $profile = $user->getSellerProfile();
        if (!$profile instanceof SellerProfile) {
            $profile = (new SellerProfile())->setStoreName($storeName ?? 'My store');
            $user->setSellerProfile($profile);
            $this->em->persist($profile);
        }
        if ($storeName !== null) {
            $profile->setStoreName($storeName);
        }
        if ($bio !== null) {
            $profile->setBio($bio);
        }
        $this->em->flush();
    }
}
