<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

final class OrderPlacedEvent extends Event
{
    public function __construct(public readonly Order $order)
    {
    }
}
