<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProductVoter extends Voter
{
    public const EDIT = 'PRODUCT_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Product && $attribute === self::EDIT;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }
        \assert($subject instanceof Product);

        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        return $subject->getSeller()?->getId() === $user->getId();
    }
}
