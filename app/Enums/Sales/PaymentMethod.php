<?php

namespace App\Enums\Sales;

enum PaymentMethod: string
{
    case CASH         = 'cash';
    case BKASH        = 'bkash';
    case NAGAD        = 'nagad';
    case ROCKET       = 'rocket';
    case BANK         = 'bank';
    case STORE_CREDIT = 'store_credit';
    case COD          = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::CASH         => 'Cash',
            self::BKASH        => 'bKash',
            self::NAGAD        => 'Nagad',
            self::ROCKET       => 'Rocket',
            self::BANK         => 'Bank',
            self::STORE_CREDIT => 'Store Credit',
            self::COD          => 'Cash on Delivery',
        };
    }
}
