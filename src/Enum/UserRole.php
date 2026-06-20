<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case Admin = 'ROLE_ADMIN';
    case Seller = 'ROLE_SELLER';
    case Customer = 'ROLE_CUSTOMER';
}
