<?php

namespace App\OrderIntake;

use App\Enums\Sales\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Support\PhoneNumber;

/**
 * Customers matching some phone numbers, with what an order-entry screen
 * shows next to them: saved name/address, order and cancel counts, and any
 * order placed in the last 24 hours (a likely duplicate). Shared by the
 * bulk sheet's phone lookups and AI Order.
 */
final class CustomerDirectory
{
    /**
     * @param  list<string>  $phones  as typed — keys of the result
     * @return array<string, array<string, mixed>>
     */
    public static function lookup(array $phones): array
    {
        $byNational = collect($phones)
            ->filter(fn ($p) => is_string($p) && trim($p) !== '')
            ->take(500)
            ->mapWithKeys(fn ($p) => [$p => PhoneNumber::national($p)])
            ->filter(fn ($n) => strlen($n) >= 6);

        if ($byNational->isEmpty()) {
            return [];
        }

        $customers = Customer::query()
            ->whereIn('phone', $byNational->unique()->values())
            ->with(['deliveryAddresses' => fn ($q) => $q->orderByDesc('is_default_shipping')->orderByDesc('id')])
            ->withCount([
                'orders',
                'orders as cancelled_count' => fn ($q) => $q->whereIn('status', [OrderStatus::CANCELLED, OrderStatus::RETURNED]),
            ])
            ->get()
            ->keyBy('phone');

        $recent = Order::query()
            ->whereIn('customer_id', $customers->pluck('id'))
            ->where('created_at', '>=', now()->subDay())
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->orderByDesc('id')
            ->get(['id', 'customer_id', 'created_at'])
            ->groupBy('customer_id');

        return $byNational->map(function ($national) use ($customers, $recent) {
            $c = $customers->get($national);

            if (! $c) {
                return ['found' => false, 'national' => $national];
            }

            $last = $recent->get($c->id)?->first();

            return [
                'found'     => true,
                'national'  => $national,
                'id'        => $c->id,
                'name'      => $c->full_name,
                'address'   => $c->deliveryAddresses->first()?->full_address,
                'orders'    => $c->orders_count,
                'cancelled' => $c->cancelled_count,
                'recent'    => $last ? ['id' => $last->id, 'ago' => $last->created_at->diffForHumans(), 'url' => route('admin.sales.orders.show', $last->id)] : null,
                'url'       => route('admin.sales.orders', ['search' => $c->phone]),
            ];
        })->all();
    }
}
