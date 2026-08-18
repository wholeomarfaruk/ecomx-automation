<?php

namespace App\Enums\Sales;

enum OfferType: string
{
    case PERCENTAGE   = 'percentage';
    case FIXED        = 'fixed';
    case BUY_X_GET_Y  = 'buy_x_get_y';
    case FIXED_PRICE  = 'fixed_price';
    case FREE_ITEM    = 'free_item';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE  => 'Percentage Discount',
            self::FIXED       => 'Fixed Discount',
            self::BUY_X_GET_Y => 'Buy X Get Y',
            self::FIXED_PRICE => 'Fixed Price',
            self::FREE_ITEM   => 'Free Item',
        };
    }
}
