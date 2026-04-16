<?php

namespace App\Enums;

enum InventoryType: string
{
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::STOCK_IN => 'Stock In',
            self::STOCK_OUT => 'Stock Out',
            self::ADJUSTMENT => 'Adjustment',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
