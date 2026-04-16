<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case BRANCH_MANAGER = 'branch_manager';
    case STAFF = 'staff';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::STAFF => 'Staff',
            self::CUSTOMER => 'Customer',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
