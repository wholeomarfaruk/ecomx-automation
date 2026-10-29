<?php

namespace App\Enums\Sales;

/**
 * An exchange's own flow — separate from the order's status, which stays
 * where the first delivery left it (Delivered).
 *
 *   requested  → new goods set aside, no parcel yet
 *   booked     → exchange parcel booked with the courier
 *   in_transit → picked up / on the way
 *   delivered  → new goods handed over, the old ones are coming back
 *   returning  → the courier reports the parcel coming back — someone must
 *                say whether it's the old goods (Completed) or the new ones (Failed)
 *   completed  → old goods received at the shop and restocked
 *   failed     → the customer refused; the new goods came back
 *   cancelled  → dropped before it went out
 */
enum ExchangeStatus: string
{
    case REQUESTED  = 'requested';
    case BOOKED     = 'booked';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED  = 'delivered';
    case RETURNING  = 'returning';
    case COMPLETED  = 'completed';
    case FAILED     = 'failed';
    case CANCELLED  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::REQUESTED  => 'Requested',
            self::BOOKED     => 'Parcel booked',
            self::IN_TRANSIT => 'In transit',
            self::DELIVERED  => 'Delivered — old item coming back',
            self::RETURNING  => 'Parcel returning',
            self::COMPLETED  => 'Completed',
            self::FAILED     => 'Failed',
            self::CANCELLED  => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::REQUESTED  => 'bg-amber-50 text-amber-700',
            self::BOOKED     => 'bg-blue-50 text-blue-600',
            self::IN_TRANSIT => 'bg-indigo-50 text-indigo-600',
            self::DELIVERED  => 'bg-teal-50 text-teal-700',
            self::RETURNING  => 'bg-orange-50 text-orange-600',
            self::COMPLETED  => 'bg-emerald-50 text-emerald-700',
            self::FAILED     => 'bg-red-50 text-red-600',
            self::CANCELLED  => 'bg-gray-100 text-gray-500',
        };
    }

    /** Still running — its old goods are owed back and its new goods are out. */
    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    /** @return list<self> */
    public static function active(): array
    {
        return [self::REQUESTED, self::BOOKED, self::IN_TRANSIT, self::DELIVERED, self::RETURNING];
    }

    /** @return list<string> */
    public static function activeValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::active());
    }

    /** Only these can take a parcel (book / attach one). */
    public function canShip(): bool
    {
        return $this === self::REQUESTED;
    }

    /**
     * "Old item received": the parcel reached the customer (or is on its way
     * back), or there's no parcel at all — swapped over the counter.
     */
    public function canComplete(): bool
    {
        return in_array($this, [self::REQUESTED, self::DELIVERED, self::RETURNING], true);
    }

    /** "Customer refused": only once the parcel actually went out. */
    public function canFail(): bool
    {
        return in_array($this, [self::IN_TRANSIT, self::DELIVERED, self::RETURNING], true);
    }

    /** A parcel booked but not picked up can be taken off (e.g. attached by mistake). */
    public function canDetach(): bool
    {
        return $this === self::BOOKED;
    }

    /**
     * Where an exchange parcel's courier update moves the exchange — forward
     * only, and never automatically to Completed/Failed (a courier "returned"
     * can mean either; someone confirms which).
     */
    public function afterCourier(CourierStatus $courier): ?self
    {
        $target = match ($courier) {
            CourierStatus::PENDING => self::BOOKED,
            CourierStatus::PICKED_UP, CourierStatus::IN_TRANSIT, CourierStatus::OUT_FOR_DELIVERY => self::IN_TRANSIT,
            CourierStatus::DELIVERED, CourierStatus::PARTIAL_DELIVERED => self::DELIVERED,
            CourierStatus::RETURNING, CourierStatus::RETURNED => self::RETURNING,
            // The parcel was cancelled at the courier — back to waiting for one.
            CourierStatus::CANCELLED => self::REQUESTED,
            default => null,
        };

        if (! $target || ! $this->isActive()) {
            return null;
        }

        if ($target === self::REQUESTED) {
            return in_array($this, [self::BOOKED, self::IN_TRANSIT], true) ? self::REQUESTED : null;
        }

        $order = [self::REQUESTED, self::BOOKED, self::IN_TRANSIT, self::DELIVERED, self::RETURNING];

        return array_search($target, $order, true) > array_search($this, $order, true) ? $target : null;
    }
}
