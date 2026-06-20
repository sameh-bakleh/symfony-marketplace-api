<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductWriteRequest
{
    public function __construct(
        #[Assert\Length(min: 2, max: 200)]
        public ?string $title = null,
        public ?string $description = null,
        #[Assert\PositiveOrZero]
        public ?int $priceMinor = null,
        #[Assert\Length(exactly: 3)]
        public ?string $currency = null,
        #[Assert\Choice(choices: ['draft', 'published', 'archived'])]
        public ?string $status = null,
        public ?int $categoryId = null,
    ) {
    }
}
