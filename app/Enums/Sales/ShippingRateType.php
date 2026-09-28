<?php

namespace App\Enums\Sales;

/**
 * How a shipping method prices an order. Each type reads its own keys from
 * ShippingMethod::rate_config — see ShippingCalculator::baseCharge().
 */
enum ShippingRateType: string
{
    case FLAT           = 'flat';
    case WEIGHT         = 'weight';
    case WEIGHT_BANDS   = 'weight_bands';
    case QUANTITY       = 'quantity';
    case SUBTOTAL_BANDS = 'subtotal_bands';
    case PERCENTAGE     = 'percentage';
    case FREE           = 'free';

    public function label(): string
    {
        return match ($this) {
            self::FLAT           => 'Flat rate',
            self::WEIGHT         => 'By weight (base + per kg)',
            self::WEIGHT_BANDS   => 'Weight bands',
            self::QUANTITY       => 'By quantity',
            self::SUBTOTAL_BANDS => 'Cart amount bands',
            self::PERCENTAGE     => 'Percentage of cart',
            self::FREE           => 'Free delivery',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::FLAT           => 'One fixed charge per order.',
            self::WEIGHT         => 'Base charge covers the first kg(s); each extra kg (rounded up) adds a charge.',
            self::WEIGHT_BANDS   => 'Pick the charge from the band the total weight falls in.',
            self::QUANTITY       => 'Charge for the first item, plus a charge for each additional item.',
            self::SUBTOTAL_BANDS => 'Pick the charge from the band the cart amount falls in.',
            self::PERCENTAGE     => 'A percentage of the cart amount, kept within a min and max.',
            self::FREE           => 'Always free.',
        };
    }

    public function usesWeight(): bool
    {
        return $this === self::WEIGHT || $this === self::WEIGHT_BANDS;
    }

    public function usesBands(): bool
    {
        return $this === self::WEIGHT_BANDS || $this === self::SUBTOTAL_BANDS;
    }
}
