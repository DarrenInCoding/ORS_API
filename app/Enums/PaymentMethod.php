<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case E_WALLET = 'e_wallet';
    case CREDIT_DEDUCTION = 'credit_deduction';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::E_WALLET => 'E-Wallet',
            self::CREDIT_DEDUCTION => 'Credit Deduction',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
