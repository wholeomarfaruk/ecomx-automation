<?php

namespace App\Enums\Sales;

enum PromotionType: string
{
    case COUPON = 'coupon';
    case OFFER  = 'offer';

    public function label(): string
    {
        return match ($this) {
            self::COUPON => 'Coupon',
            self::OFFER  => 'Offer',
        };
    }
}
