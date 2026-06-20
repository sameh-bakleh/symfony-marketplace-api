<?php

declare(strict_types=1);

namespace App\ApiResource;

use App\Entity\User;

final class UserProfileResource
{
    /** @return array<string, mixed> */
    public static function fromEntity(User $u): array
    {
        $seller = null;
        if ($u->getSellerProfile() !== null) {
            $sp = $u->getSellerProfile();
            $seller = [
                'storeName' => $sp->getStoreName(),
                'bio' => $sp->getBio(),
                'verified' => $sp->isVerified(),
            ];
        }

        return [
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'displayName' => $u->getDisplayName(),
            'phone' => $u->getPhone(),
            'roles' => $u->getRoles(),
            'seller' => $seller,
        ];
    }
}
