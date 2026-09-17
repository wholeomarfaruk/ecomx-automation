<?php

namespace App\Enums\Sales;

/**
 * The single source of truth for when stock gets booked/released and when
 * accounting gets posted — OrderDetail and StockService branch on the
 * grouping methods below (isBookable(), isClosed()) instead of scattering
 * in_array() checks against raw status lists. Booking happens the moment a
 * status enters the "bookable" group (confirmed onward); accounting +
 * physical stock deduction only ever happen at COMPLETED, never at
 * delivered — delivered is a pure shipping-status marker.
 */
enum OrderStatus: string
{
    case PENDING             = 'pending';
    case CONFIRMED           = 'confirmed';
    case PROCESSING          = 'processing';
    case SHIPPED              = 'shipped';
    case PARTIALLY_DELIVERED = 'partially_delivered';
    case DELIVERED           = 'delivered';
    case COMPLETED           = 'completed';
    case CANCELLED           = 'cancelled';
    case RETURNING            = 'returning';
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
            self::PARTIALLY_DELIVERED => 'Partially Delivered',
            self::DELIVERED           => 'Delivered',
            self::COMPLETED           => 'Completed',
            self::CANCELLED           => 'Cancelled',
            self::RETURNING            => 'Returning',
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
            self::PARTIALLY_DELIVERED => 'bg-teal-50 text-teal-600',
            self::DELIVERED           => 'bg-emerald-50 text-emerald-600',
            self::COMPLETED           => 'bg-green-100 text-green-700',
            self::CANCELLED           => 'bg-gray-100 text-gray-500',
            self::RETURNING            => 'bg-orange-50 text-orange-600',
            self::RETURNED            => 'bg-red-50 text-red-500',
            self::PARTIALLY_RETURNED  => 'bg-orange-50 text-orange-600',
            self::REFUNDED            => 'bg-rose-50 text-rose-500',
        };
    }

    /**
     * True while stock should be held as "booked" (soft-reserved) against
     * this order — from confirmed all the way through delivered, since
     * delivered no longer triggers a physical deduction on its own. Booking
     * only releases once the order leaves this group (completed, cancelled,
     * returning, returned, partially_returned, refunded).
     */
    public function isBookable(): bool
    {
        return match ($this) {
            self::CONFIRMED, self::PROCESSING, self::SHIPPED,
            self::PARTIALLY_DELIVERED, self::DELIVERED => true,
            default => false,
        };
    }

    /**
     * True once an order can no longer move through the normal booking ->
     * completion flow — it's been finalized one way or another.
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::RETURNED, self::REFUNDED => true,
            default => false,
        };
    }

    /**
     * Only COMPLETED (and a post-completion return reversal) ever changes
     * physical inventory or posts accounting — every earlier status is
     * booking/tracking only.
     */
    public function affectsInventoryPhysically(): bool
    {
        return match ($this) {
            self::COMPLETED, self::RETURNING, self::RETURNED, self::PARTIALLY_RETURNED => true,
            default => false,
        };
    }

    public function affectsAccounting(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::RETURNING,
            self::RETURNED, self::PARTIALLY_RETURNED => true,
            default => false,
        };
    }
}
