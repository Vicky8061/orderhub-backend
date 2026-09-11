<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CASHIER = 'cashier';
    case KITCHEN = 'kitchen';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
