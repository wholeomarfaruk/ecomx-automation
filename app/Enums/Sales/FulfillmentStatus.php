<?php

namespace App\Enums\Sales;

enum FulfillmentStatus: string
{
    case UNFULFILLED = 'unfulfilled';
    case PARTIAL     = 'partial';
    case FULFILLED   = 'fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::UNFULFILLED => 'Unfulfilled',
            self::PARTIAL     => 'Partial',
            self::FULFILLED   => 'Fulfilled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::UNFULFILLED => 'bg-gray-100 text-gray-500',
            self::PARTIAL     => 'bg-amber-50 text-amber-600',
            self::FULFILLED   => 'bg-emerald-50 text-emerald-600',
        };
    }
}
