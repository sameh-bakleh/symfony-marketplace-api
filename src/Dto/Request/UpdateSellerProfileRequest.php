<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateSellerProfileRequest
{
    public function __construct(
        #[Assert\Length(min: 2, max: 160)]
        public ?string $storeName = null,
        public ?string $bio = null,
    ) {
    }
}
