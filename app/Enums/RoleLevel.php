<?php

namespace App\Enums;

enum RoleLevel: int
{
    case SUPERADMIN = 1;
    case ADMIN = 2;
    case OPERATOR = 3;
    case USER = 4;

    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::OPERATOR => 'Operator',
            self::USER => 'User',
        };
    }
}