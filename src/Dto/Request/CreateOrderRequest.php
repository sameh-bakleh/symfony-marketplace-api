<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderRequest
{
    /**
     * Each item: { "productId": int, "quantity": int }.
     *
     * @var list<array<string, int>>
     */
    #[Assert\NotNull]
    #[Assert\Count(min: 1, max: 50)]
    public array $items = [];
}
