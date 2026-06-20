<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CategoryWriteRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        public string $name = '',
        public ?string $description = null,
        public ?int $parentId = null,
        public bool $isActive = true,
    ) {
    }
}
