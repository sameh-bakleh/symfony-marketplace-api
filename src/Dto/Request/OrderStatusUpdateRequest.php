<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderStatusUpdateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])]
        public string $status = '',
    ) {
    }
}
