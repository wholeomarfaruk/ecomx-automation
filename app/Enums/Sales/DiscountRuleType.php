<?php

namespace App\Enums\Sales;

enum DiscountRuleType: string
{
    case PERCENTAGE     = 'percentage';
    case FIXED          = 'fixed';
    case FIXED_PRICE    = 'fixed_price';
    case BUY_X_GET_Y    = 'buy_x_get_y';
    case FREE_ITEM      = 'free_item';
    case FREE_SHIPPING  = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE    => 'Percentage',
            self::FIXED         => 'Fixed Amount',
            self::FIXED_PRICE   => 'Fixed Price',
            self::BUY_X_GET_Y   => 'Buy X Get Y',
            self::FREE_ITEM     => 'Free Item',
            self::FREE_SHIPPING => 'Free Shipping',
        };
    }
}
