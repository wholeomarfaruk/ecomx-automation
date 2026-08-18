<?php

namespace App\Enums\Sales;

enum OrderStatus: string
{
    case PENDING             = 'pending';
    case CONFIRMED           = 'confirmed';
    case PROCESSING          = 'processing';
    case SHIPPED              = 'shipped';
    case DELIVERED           = 'delivered';
    case PARTIALLY_DELIVERED = 'partially_delivered';
    case CANCELLED           = 'cancelled';
    case RETURNED            = 'returned';
    case PARTIALLY_RETURNED  = 'partially_returned';
    case REFUNDED            = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING             => 'Pending',
            self::CONFIRMED           => 'Confirmed',
            self::PROCESSING          => 'Processing',
            self::SHIPPED              => 'Shipped',
            self::DELIVERED           => 'Delivered',
            self::PARTIALLY_DELIVERED => 'Partially Delivered',
            self::CANCELLED           => 'Cancelled',
            self::RETURNED            => 'Returned',
            self::PARTIALLY_RETURNED  => 'Partially Returned',
            self::REFUNDED            => 'Refunded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING             => 'bg-amber-50 text-amber-600',
            self::CONFIRMED           => 'bg-blue-50 text-blue-600',
            self::PROCESSING          => 'bg-indigo-50 text-indigo-600',
            self::SHIPPED              => 'bg-purple-50 text-purple-600',
            self::DELIVERED           => 'bg-emerald-50 text-emerald-600',
            self::PARTIALLY_DELIVERED => 'bg-teal-50 text-teal-600',
            self::CANCELLED           => 'bg-gray-100 text-gray-500',
            self::RETURNED            => 'bg-red-50 text-red-500',
            self::PARTIALLY_RETURNED  => 'bg-orange-50 text-orange-600',
            self::REFUNDED            => 'bg-rose-50 text-rose-500',
        };
    }
}
