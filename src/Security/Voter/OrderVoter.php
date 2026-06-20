<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';

    public const UPDATE_STATUS = 'ORDER_UPDATE_STATUS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Order
            && \in_array($attribute, [self::VIEW, self::UPDATE_STATUS], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        \assert($subject instanceof Order);

        return match ($attribute) {
            self::VIEW => $user->hasRole('ROLE_ADMIN')
                || $subject->getCustomer()?->getId() === $user->getId(),
            self::UPDATE_STATUS => $user->hasRole('ROLE_ADMIN'),
            default => false,
        };
    }
}
