<?php

namespace App\Actions\Sales;

use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * "Duplicate / re-order" from the admin order page: a new Pending admin
 * order with the same customer, addresses, lines (same unit prices), shipping,
 * discount, tax, extra charges and notes. Deliberately NOT copied: payments,
 * courier details, coupon, storefront offers (line offer discounts reset) and
 * packing/delivery/return progress — those belong to the original order.
 * Pending means no stock is booked until the new order is confirmed.
 */
class DuplicateOrder
{
    public function handle(Order $source): Order
    {
        $source->loadMissing('items', 'charges');

        return DB::transaction(function () use ($source) {
            $order = Order::create([
                'customer_id'         => $source->customer_id,
                'source'              => OrderSource::ADMIN,
                'status'              => OrderStatus::PENDING,
                'payment_status'      => PaymentStatus::PENDING,
                'fulfillment_status'  => FulfillmentStatus::UNFULFILLED,
                'currency'            => $source->currency,
                'shipping_amount'     => $source->shipping_amount,
                'discount_amount'     => $source->discount_amount,
                'tax_amount'          => $source->tax_amount,
                'billing_address_id'  => $source->billing_address_id,
                'shipping_address_id' => $source->shipping_address_id,
                'customer_note'       => $source->customer_note,
                'admin_note'          => trim("Duplicated from Order #{$source->id}\n" . ($source->admin_note ?? '')),
                'placed_at'           => now(),
            ]);

            foreach ($source->items as $item) {
                /** @var OrderItem $item */
                $order->items()->create([
                    'product_id'     => $item->product_id,
                    'variant_id'     => $item->variant_id,
                    'combo_id'       => $item->combo_id,
                    'is_gift'        => $item->is_gift,
                    'product_name'   => $item->product_name,
                    'variant_name'   => $item->variant_name,
                    'sku'            => $item->sku,
                    'quantity'       => $item->quantity,
                    'unit_price'     => $item->unit_price,
                    'purchase_price' => $item->purchase_price,
                    'total_amount'   => $item->is_gift ? 0 : round((float) $item->unit_price * (float) $item->quantity, 2),
                ]);
            }

            foreach ($source->charges as $charge) {
                $order->charges()->create(['label' => $charge->label, 'amount' => $charge->amount]);
            }

            $order->recalculateTotals();

            // The source's discount was sized for its (possibly offer-reduced)
            // lines; lines here are at full price, so it still fits — but
            // never let it exceed the new subtotal.
            if ((float) $order->discount_amount > (float) $order->subtotal) {
                $order->update(['discount_amount' => $order->subtotal]);
                $order->recalculateTotals();
            }

            return $order;
        });
    }
}
