<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\MarketplaceNotification;
use App\Entity\User;
use App\Message\PersistNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PersistNotificationMessageHandler
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function __invoke(PersistNotificationMessage $message): void
    {
        $user = $this->em->find(User::class, $message->userId);
        if (!$user instanceof User) {
            return;
        }
        $n = (new MarketplaceNotification())
            ->setUser($user)
            ->setType($message->type)
            ->setTitle($message->title)
            ->setBody($message->body)
            ->setContext($message->context);
        $this->em->persist($n);
        $this->em->flush();
    }
}
