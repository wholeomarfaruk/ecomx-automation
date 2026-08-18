<?php

namespace App\Enums\Sales;

enum PaymentStatus: string
{
    case PENDING  = 'pending';
    case PARTIAL  = 'partial';
    case PAID     = 'paid';
    case FAILED   = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::PARTIAL  => 'Partial',
            self::PAID     => 'Paid',
            self::FAILED   => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING  => 'bg-amber-50 text-amber-600',
            self::PARTIAL  => 'bg-orange-50 text-orange-600',
            self::PAID     => 'bg-emerald-50 text-emerald-600',
            self::FAILED   => 'bg-red-50 text-red-500',
            self::REFUNDED => 'bg-rose-50 text-rose-500',
        };
    }
}
