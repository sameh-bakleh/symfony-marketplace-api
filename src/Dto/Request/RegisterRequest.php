<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 128)]
        public string $password = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 120)]
        public string $displayName = '',
        #[Assert\Choice(choices: ['customer', 'seller'])]
        public string $role = 'customer',
        #[Assert\Length(max: 32)]
        public ?string $phone = null,
        #[Assert\Length(max: 160)]
        public ?string $storeName = null,
    ) {
    }
}
