<?php

namespace App\Enums\Sales;

enum CouponCustomerStatus: string
{
    case SAVED    = 'saved';
    case REDEEMED = 'redeemed';
    case EXPIRED  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::SAVED    => 'Saved',
            self::REDEEMED => 'Redeemed',
            self::EXPIRED  => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SAVED    => 'bg-blue-50 text-blue-600',
            self::REDEEMED => 'bg-emerald-50 text-emerald-600',
            self::EXPIRED  => 'bg-gray-100 text-gray-500',
        };
    }
}
