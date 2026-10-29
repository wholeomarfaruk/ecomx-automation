<?php

namespace App\Actions\Sales\Exchanges;

use App\Actions\Sales\ReceiveReturn;
use App\Enums\Sales\ExchangeStatus;
use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\OrderExchange;
use App\Models\OrderExchangeItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Opens an exchange inside a delivered order — no new order. The units
 * coming back are recorded (not yet returned: they're still with the
 * customer), the new goods are added as order lines tagged with the
 * exchange and taken from stock now (they go out in the exchange parcel),
 * and an optional delivery charge for the exchange is added as an order
 * charge. The order total drops by the credit for the units coming back
 * (Order::exchangeCreditAmount()), so its due is exactly what the exchange
 * parcel should collect.
 */
class CreateExchange
{
    public function __construct(protected StockService $stockService) {}

    /**
     * @param  array<int, float|string>  $returns  order item id => quantity coming back
     * @param  list<array{product_id: int|string, variant_id?: int|string|null, quantity: float|string, unit_price: float|string}>  $newLines
     * @param  list<array{label: string, amount: float|string}>  $extraCharges  besides the delivery charge (packaging, alteration, ...)
     *
     * @throws DomainException|\App\Exceptions\Inventory\InsufficientStockException
     */
    public function handle(Order $order, array $returns, array $newLines, float $charge, ?string $reason = null, ?string $note = null, array $extraCharges = []): OrderExchange
    {
        if ($order->status !== OrderStatus::DELIVERED) {
            throw new DomainException('An exchange can only be made on a delivered order.');
        }

        // A partial delivery's leftover is still on its way back — what the customer
        // really has isn't known until it's received.
        if (ReceiveReturn::awaiting($order)) {
            throw new DomainException('This order has a returned parcel waiting — receive it first (Orders → Return received).');
        }

        $order->loadMissing('items');
        $available = static::exchangeableQuantities($order);

        $returns = collect($returns)
            ->map(fn ($qty) => round(max(0.0, (float) $qty), 3))
            ->filter(fn ($qty) => $qty > 0);

        if ($returns->isEmpty()) {
            throw new DomainException('Pick what the customer is sending back.');
        }

        foreach ($returns as $itemId => $qty) {
            if (! isset($available[$itemId])) {
                throw new DomainException('That item is not part of this order.');
            }

            if ($qty > $available[$itemId] + 0.0005) {
                $item = $order->items->firstWhere('id', $itemId);
                throw new DomainException("{$item->product_name}: only " . rtrim(rtrim(number_format($available[$itemId], 3), '0'), '.') . ' can be exchanged.');
            }
        }

        $newLines = array_values(array_filter($newLines, fn ($l) => ! empty($l['product_id']) && (float) ($l['quantity'] ?? 0) > 0));

        if ($newLines === []) {
            throw new DomainException('Add what the customer gets instead.');
        }

        $extraCharges = collect($extraCharges)
            ->map(fn ($c) => ['label' => trim((string) ($c['label'] ?? '')), 'amount' => round(max(0.0, (float) ($c['amount'] ?? 0)), 2)])
            ->filter(fn ($c) => $c['label'] !== '' && $c['amount'] > 0)
            ->values();
        $charge = round(max(0.0, $charge), 2);

        return DB::transaction(function () use ($order, $returns, $newLines, $charge, $reason, $note, $extraCharges) {
            $number = (int) OrderExchange::where('order_id', $order->id)->lockForUpdate()->max('number') + 1;

            $exchange = OrderExchange::create([
                'order_id'      => $order->id,
                'number'        => $number,
                'status'        => ExchangeStatus::REQUESTED,
                'reason'        => $reason ?: null,
                'note'          => $note ?: null,
                // Delivery charge + extras — everything the exchange adds to the order.
                'charge_amount' => round($charge + $extraCharges->sum('amount'), 2),
                'created_by'    => auth()->id(),
            ]);

            foreach ($returns as $itemId => $qty) {
                OrderExchangeItem::create(['order_exchange_id' => $exchange->id, 'order_item_id' => $itemId, 'quantity' => $qty]);
            }

            $added = [];

            foreach ($newLines as $line) {
                $product = Product::findOrFail((int) $line['product_id']);
                $variant = ! empty($line['variant_id']) ? ProductVariant::where('product_id', $product->id)->findOrFail((int) $line['variant_id']) : null;
                $quantity = round((float) $line['quantity'], 3);
                $unitPrice = round(max(0.0, (float) $line['unit_price']), 2);

                $item = $order->items()->create([
                    'product_id'      => $product->id,
                    'variant_id'      => $variant?->id,
                    'exchange_id'     => $exchange->id,
                    'product_name'    => $product->name,
                    'variant_name'    => $variant?->sku,
                    'sku'             => $variant?->sku ?? $product->code,
                    'quantity'        => $quantity,
                    'unit_price'      => $unitPrice,
                    'purchase_price'  => $variant?->purchase_price ?? $product->purchase_price,
                    'discount_amount' => 0,
                    'total_amount'    => round($unitPrice * $quantity, 2),
                ]);

                // Out of stock now — it leaves in the exchange parcel.
                $this->stockService->bookItem($item->fresh(['product', 'variant', 'order']), "Order #{$order->id} exchange #{$number}");

                $added[] = "{$quantity} × {$product->name}" . ($variant ? " ({$variant->sku})" : '');
            }

            if ($charge > 0) {
                $chargeRow = $order->charges()->create(['exchange_id' => $exchange->id, 'label' => "Exchange #{$number} delivery", 'amount' => $charge]);
                $exchange->update(['order_charge_id' => $chargeRow->id]);
            }

            foreach ($extraCharges as $extra) {
                $order->charges()->create(['exchange_id' => $exchange->id, 'label' => "Exchange #{$number} — {$extra['label']}", 'amount' => $extra['amount']]);
            }

            $order->recalculateTotals();
            $order->refresh()->syncPaymentStatus();

            $back = $returns->map(fn ($qty, $id) => rtrim(rtrim(number_format($qty, 3), '0'), '.') . ' × ' . $order->items->firstWhere('id', $id)?->product_name)->implode(', ');

            activity('sales')->causedBy(auth()->user())->performedOn($order)->event('updated')
                ->withProperties(['changes' => ['exchange' => $number, 'back' => $back, 'new' => $added, 'delivery_charge' => $charge, 'extra_charges' => $extraCharges->all(), 'reason' => $reason]])
                ->log("Exchange #{$number} opened for Order #{$order->id}: {$back} → " . implode(', ', $added));

            return $exchange;
        });
    }

    /**
     * How much of each line can still go into an exchange: delivered, not
     * returned, not already owed back by another running exchange.
     *
     * @return array<int, float>  order item id => quantity
     */
    public static function exchangeableQuantities(Order $order): array
    {
        $pending = static::pendingQuantities($order);

        return $order->items
            // A line still on its way in a running exchange isn't the customer's yet.
            ->reject(fn (OrderItem $item) => $item->exchange_id && $item->exchange?->status?->isActive())
            ->mapWithKeys(fn (OrderItem $item) => [
                $item->id => max(0.0, round((float) $item->quantity - (float) $item->returned_quantity - (float) ($pending[$item->id] ?? 0), 3)),
            ])
            ->filter(fn ($qty) => $qty > 0)
            ->all();
    }

    /**
     * Units running exchanges are still owed back, per line — not returned
     * yet, but already credited (Order::exchangeCreditAmount()).
     *
     * @return array<int, float>  order item id => quantity
     */
    public static function pendingQuantities(Order $order): array
    {
        return OrderExchangeItem::query()
            ->join('order_exchanges', 'order_exchanges.id', '=', 'order_exchange_items.order_exchange_id')
            ->where('order_exchanges.order_id', $order->id)
            ->whereIn('order_exchanges.status', ExchangeStatus::activeValues())
            ->groupBy('order_exchange_items.order_item_id')
            ->selectRaw('order_exchange_items.order_item_id, SUM(order_exchange_items.quantity) AS qty')
            ->pluck('qty', 'order_item_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();
    }
}
