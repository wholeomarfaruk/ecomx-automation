<?php

namespace App\Enums\Sales;

enum PromotionStatus: string
{
    case DRAFT    = 'draft';
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
    case EXPIRED  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT    => 'Draft',
            self::ACTIVE   => 'Active',
            self::INACTIVE => 'Inactive',
            self::EXPIRED  => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT    => 'bg-amber-50 text-amber-600',
            self::ACTIVE   => 'bg-emerald-50 text-emerald-600',
            self::INACTIVE => 'bg-gray-100 text-gray-500',
            self::EXPIRED  => 'bg-red-50 text-red-500',
        };
    }
}
