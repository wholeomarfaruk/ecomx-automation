<?php

namespace App\Enums\Sales;

enum OrderPaymentType: string
{
    case PAYMENT = 'payment';
    case REFUND  = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT => 'Payment',
            self::REFUND  => 'Refund',
        };
    }
}
