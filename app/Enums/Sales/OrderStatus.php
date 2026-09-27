<?php

namespace App\Enums\Sales;

/**
 * The single source of truth for when stock gets booked/released — every
 * status change (OrderDetail, the Orders table, ApplyCourierStatus) branches
 * on the grouping methods below (isBookable(), isClosed()) instead of
 * scattering in_array() checks against raw status lists. Booking happens the
 * moment a status enters the "bookable" group (confirmed onward).
 *
 * A partial delivery is DELIVERED with FulfillmentStatus::PARTIAL — there is
 * no separate partially-delivered status. There is no COMPLETED status
 * either: DELIVERED is the end of the normal flow, and sale accounting is not
 * posted from any status change yet.
 */
enum OrderStatus: string
{
    case PENDING             = 'pending';
    case CONFIRMED           = 'confirmed';
    case PROCESSING          = 'processing';
    case SHIPPED              = 'shipped';
    case DELIVERED           = 'delivered';
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
            self::DELIVERED           => 'Delivered',
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
            self::DELIVERED           => 'bg-emerald-50 text-emerald-600',
            self::CANCELLED           => 'bg-gray-100 text-gray-500',
            self::RETURNING            => 'bg-orange-50 text-orange-600',
            self::RETURNED            => 'bg-red-50 text-red-500',
            self::PARTIALLY_RETURNED  => 'bg-orange-50 text-orange-600',
            self::REFUNDED            => 'bg-rose-50 text-rose-500',
        };
    }

    /**
     * True while stock should be held as "booked" (soft-reserved) against
     * this order — from confirmed all the way through delivered. Booking
     * only releases once the order leaves this group (cancelled, returning,
     * returned, partially_returned, refunded).
     */
    public function isBookable(): bool
    {
        return match ($this) {
            self::CONFIRMED, self::PROCESSING, self::SHIPPED, self::DELIVERED => true,
            default => false,
        };
    }

    /**
     * True once an order can no longer move through the normal
     * booking -> delivery flow — it's been finalized one way or another.
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::CANCELLED, self::RETURNED, self::REFUNDED => true,
            default => false,
        };
    }

    /** The stock ledger type to release this order's booking under when it moves into this (non-bookable) status. */
    public function bookingReleaseType(): string
    {
        return match ($this) {
            self::RETURNING, self::RETURNED, self::PARTIALLY_RETURNED => 'unbooked_returned',
            default => 'unbooked_cancelled',
        };
    }

    public function affectsInventoryPhysically(): bool
    {
        return match ($this) {
            self::RETURNING, self::RETURNED, self::PARTIALLY_RETURNED => true,
            default => false,
        };
    }

    public function affectsAccounting(): bool
    {
        return match ($this) {
            self::CANCELLED, self::RETURNING, self::RETURNED, self::PARTIALLY_RETURNED => true,
            default => false,
        };
    }
}
