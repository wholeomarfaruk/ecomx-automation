<?php

namespace App\Enums\Sales;

enum PaymentMethod: string
{
    case CASH   = 'cash';
    case BKASH  = 'bkash';
    case NAGAD  = 'nagad';
    case ROCKET = 'rocket';
    case BANK   = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::CASH   => 'Cash',
            self::BKASH  => 'bKash',
            self::NAGAD  => 'Nagad',
            self::ROCKET => 'Rocket',
            self::BANK   => 'Bank',
        };
    }
}
