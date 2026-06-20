<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    public function __construct(
        #[Assert\Length(min: 2, max: 120)]
        public ?string $displayName = null,
        #[Assert\Length(max: 32)]
        public ?string $phone = null,
    ) {
    }
}
