<?php

namespace App\Actions\Customers;

use App\Enums\Sales\OrderStatus;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

/**
 * The one admin path for adding or correcting a customer's delivery address
 * (order page's Customer & Address, the customer profile's Addresses tab).
 *
 * Orders point at an address row (no copy of it), so a change must never
 * rewrite what a shipped order shows. When the address is on an order that
 * has already gone out, the edit is saved as a new address instead: it takes
 * over the default flags, the old one is retired (inactive), and the orders
 * not shipped yet move to the new one. Used only by orders that haven't
 * shipped (or none), it's corrected in place — those orders get it too.
 */
class SaveDeliveryAddress
{
    /** Orders still editable — they follow an address correction. */
    public const OPEN_STATUSES = [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING];

    /**
     * @param  array{name: string, phone: string, alternative_phone?: ?string, full_address: string, delivery_note?: ?string}  $input
     * @return array{0: DeliveryAddress, 1: bool}  the address now in use, and whether it's a new copy of $address
     */
    public function handle(Customer $customer, ?DeliveryAddress $address, array $input): array
    {
        $values = [
            'name'              => trim($input['name']),
            'phone'             => PhoneNumber::national($input['phone']),
            'alternative_phone' => trim((string) ($input['alternative_phone'] ?? '')) !== '' ? PhoneNumber::national($input['alternative_phone']) : null,
            'full_address'      => trim($input['full_address']),
            'delivery_note'     => trim((string) ($input['delivery_note'] ?? '')) ?: null,
        ];

        return DB::transaction(function () use ($customer, $address, $values) {
            if (! $address) {
                $first = ! $customer->deliveryAddresses()->exists();

                return [DeliveryAddress::create($values + [
                    'customer_id'         => $customer->id,
                    'address_type'        => 'Home',
                    'is_default_billing'  => $first,
                    'is_default_shipping' => $first,
                    'is_active'           => true,
                ]), false];
            }

            if (! static::shippedOrdersUsing($address)) {
                $address->update($values + ['is_active' => true]);

                return [$address, false];
            }

            $copy = DeliveryAddress::create($values + collect($address->only([
                'address_type', 'country_id', 'state_id', 'city_id', 'ps_id', 'area_id', 'zip_code_id', 'street_id', 'house_id', 'latitude', 'longitude',
                'is_default_billing', 'is_default_shipping',
            ]))->all() + [
                'customer_id' => $customer->id,
                'is_active'   => true,
            ]);

            $address->update(['is_default_billing' => false, 'is_default_shipping' => false, 'is_active' => false]);

            foreach (['shipping_address_id', 'billing_address_id'] as $column) {
                Order::where($column, $address->id)->whereIn('status', self::OPEN_STATUSES)->update([$column => $copy->id]);
            }

            return [$copy, true];
        });
    }

    /** How many orders that already went out show this address. */
    public static function shippedOrdersUsing(DeliveryAddress $address): int
    {
        return Order::query()
            ->where(fn ($q) => $q->where('shipping_address_id', $address->id)->orWhere('billing_address_id', $address->id))
            ->whereNotIn('status', self::OPEN_STATUSES)
            ->count();
    }
}
