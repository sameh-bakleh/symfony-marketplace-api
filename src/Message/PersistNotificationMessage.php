<?php

declare(strict_types=1);

namespace App\Message;

final readonly class PersistNotificationMessage
{
    /**
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $body,
        public ?array $context = null,
    ) {
    }
}
