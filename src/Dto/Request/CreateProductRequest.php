<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateProductRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public string $title = '',
        #[Assert\NotBlank]
        public string $description = '',
        #[Assert\Positive]
        public int $categoryId = 0,
        #[Assert\PositiveOrZero]
        public int $priceMinor = 0,
        #[Assert\Length(exactly: 3)]
        public string $currency = 'USD',
        #[Assert\Choice(choices: ['draft', 'published', 'archived'])]
        public string $status = 'draft',
    ) {
    }
}
