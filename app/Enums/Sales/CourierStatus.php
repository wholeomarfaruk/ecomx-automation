<?php

namespace App\Enums\Sales;

/**
 * PARTIAL_DELIVERED is only ever a driver's normalized reading of a
 * courier's "partially delivered" update — ApplyCourierStatus never stores
 * it: a partial delivery means the rest of the parcel is now on its way
 * back, so the shipment/order's courier_status becomes RETURNING instead.
 */
enum CourierStatus: string
{
    case PENDING           = 'pending';
    case PICKED_UP         = 'picked_up';
    case IN_TRANSIT        = 'in_transit';
    case OUT_FOR_DELIVERY  = 'out_for_delivery';
    case DELIVERED         = 'delivered';
    case PARTIAL_DELIVERED = 'partial_delivered';
    case RETURNING         = 'returning';
    case FAILED            = 'failed';
    case RETURNED          = 'returned';
    case CANCELLED         = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING          => 'Pending',
            self::PICKED_UP        => 'Picked Up',
            self::IN_TRANSIT       => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED        => 'Delivered',
            self::PARTIAL_DELIVERED => 'Partially Delivered',
            self::RETURNING        => 'Returning',
            self::FAILED           => 'Failed',
            self::RETURNED         => 'Returned',
            self::CANCELLED        => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING          => 'bg-gray-100 text-gray-500',
            self::PICKED_UP        => 'bg-blue-50 text-blue-600',
            self::IN_TRANSIT       => 'bg-indigo-50 text-indigo-600',
            self::OUT_FOR_DELIVERY => 'bg-purple-50 text-purple-600',
            self::DELIVERED        => 'bg-emerald-50 text-emerald-600',
            self::PARTIAL_DELIVERED => 'bg-teal-50 text-teal-600',
            self::RETURNING        => 'bg-amber-50 text-amber-600',
            self::FAILED           => 'bg-red-50 text-red-500',
            self::RETURNED         => 'bg-orange-50 text-orange-600',
            self::CANCELLED        => 'bg-slate-100 text-slate-500',
        };
    }
}
