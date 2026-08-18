<?php

namespace App\Enums\Sales;

enum PosCashMovementType: string
{
    case OPENING  = 'opening';
    case CLOSING  = 'closing';
    case CASH_IN  = 'cash_in';
    case CASH_OUT = 'cash_out';
    case REFUND   = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::OPENING  => 'Opening',
            self::CLOSING  => 'Closing',
            self::CASH_IN  => 'Cash In',
            self::CASH_OUT => 'Cash Out',
            self::REFUND   => 'Refund',
        };
    }

    public function isInflow(): bool
    {
        return match ($this) {
            self::OPENING, self::CASH_IN => true,
            self::CLOSING, self::CASH_OUT, self::REFUND => false,
        };
    }
}
