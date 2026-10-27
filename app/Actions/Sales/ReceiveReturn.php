<?php

namespace App\Actions\Sales;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Orders → "Return received": the courier's parcel is back in the shop.
 *
 *  - full return (order Returning): every item is back. It adds up when the
 *    customer paid nothing (refused — payment Failed) or exactly what's
 *    left without the items, i.e. the delivery charge (a paid return).
 *  - partial delivery's leftover (order Delivered, courier Returning): it
 *    adds up when the returned items are marked and the kept ones plus
 *    delivery equal what was paid.
 *
 * assess() says whether it adds up — then it's received straight away —
 * or what's off, with a suggested Kept/Returned split worked out from the
 * amount paid, so the receive-return modal can fix items, delivery charge
 * and discount in one go. handle() does the receiving either way and logs
 * everything it changed.
 */
class ReceiveReturn
{
    /** Courier amounts can be off by paisa rounding. */
    public const TOLERANCE = 0.5;

    /** Kept/Returned combinations tried when suggesting a split. */
    protected const MAX_COMBINATIONS = 5000;

    public function __construct(protected RecordOrderReturns $recordReturns) {}

    /** Whether this order has a returned parcel waiting to be received. */
    public static function awaiting(Order $order): bool
    {
        return $order->status === OrderStatus::RETURNING
            || ($order->status === OrderStatus::DELIVERED && $order->courier_status === CourierStatus::RETURNING);
    }

    /**
     * @return array{full: bool, clean: bool, issue: ?string, returned: array<int, float>, suggested: bool}
     */
    public function assess(Order $order): array
    {
        $order->loadMissing('items');
        $full = $order->status === OrderStatus::RETURNING;
        $paid = (float) $order->paid_amount;

        $current = $order->items
            ->mapWithKeys(fn (OrderItem $i) => [$i->id => $full ? (float) $i->quantity : (float) $i->returned_quantity])
            ->all();
        $total = $this->totalFor($order, $current);
        $off = abs($paid - $total) > self::TOLERANCE;

        $clean = $full
            ? $paid <= self::TOLERANCE || ! $off
            : array_sum($current) > 0 && ! $off;

        if ($clean) {
            return ['full' => $full, 'clean' => true, 'issue' => null, 'returned' => $current, 'suggested' => false];
        }

        $suggestion = $this->suggest($order, $full);

        return [
            'full' => $full,
            'clean' => false,
            'issue' => $this->issue($full, array_sum($current) > 0, $paid, $total),
            'returned' => $suggestion ?? $current,
            'suggested' => $suggestion !== null && $suggestion != $current,
        ];
    }

    /**
     * The order's total if these quantities came back — same sum as
     * Order::recalculateTotals(), with an optional delivery charge /
     * discount to try instead of the saved ones.
     *
     * @param  array<int, float|string>  $returned  item id => returned quantity
     */
    public function totalFor(Order $order, array $returned, ?float $shipping = null, ?float $discount = null): float
    {
        $itemsTotal = 0.0;
        $returnedValue = 0.0;

        foreach ($order->items as $item) {
            $qty = (float) $item->quantity;

            if ($item->is_gift || $qty <= 0) {
                continue;
            }

            $itemsTotal += (float) $item->total_amount;
            $returnedValue += (float) $item->total_amount * min(max(0.0, (float) ($returned[$item->id] ?? 0)), $qty) / $qty;
        }

        $discount = min($discount ?? (float) $order->discount_amount, $itemsTotal);
        $factor = $itemsTotal > 0 ? 1 - $discount / $itemsTotal : 1.0;
        $shipping ??= (float) $order->shipping_amount;
        $netShipping = $shipping - max(0.0, min($shipping, (float) $order->shipping_discount));

        return round($itemsTotal - $discount - $returnedValue * $factor + $netShipping + (float) $order->tax_amount + (float) $order->charges_amount, 2);
    }

    /**
     * Kept/Returned split whose total is closest to what was paid. A full
     * return keeps "everything back" unless some split matches exactly (the
     * customer actually kept something); a partial delivery's leftover must
     * bring at least one unit back. Null when nothing better can be said
     * (or there are too many combinations to try).
     *
     * @return array<int, float>|null
     */
    protected function suggest(Order $order, bool $full): ?array
    {
        $paid = (float) $order->paid_amount;
        $options = [];
        $combinations = 1;

        foreach ($order->items as $item) {
            $qty = (float) $item->quantity;
            $whole = ! $item->is_gift && $qty >= 1 && floor($qty) === $qty;
            $options[$item->id] = $whole ? range(0, (int) $qty) : [$full ? $qty : (float) $item->returned_quantity];
            $combinations *= count($options[$item->id]);
        }

        if ($combinations > self::MAX_COMBINATIONS) {
            return null;
        }

        $best = null;
        $bestDiff = INF;

        foreach ($this->combinations($options) as $returned) {
            $units = array_sum($returned);

            if (! $full && $units <= 0) {
                continue;
            }

            $diff = round(abs($paid - $this->totalFor($order, $returned)), 2);

            // Ties: a full return leans to more coming back, a leftover to less.
            $better = $diff < $bestDiff - 0.004
                || (abs($diff - $bestDiff) <= 0.004 && ($full ? $units > array_sum($best) : $units < array_sum($best)));

            if ($better) {
                [$best, $bestDiff] = [$returned, $diff];
            }
        }

        if ($best === null || ($full && $bestDiff > self::TOLERANCE)) {
            return null;
        }

        return $best;
    }

    /**
     * @param  array<int, list<float|int>>  $options  item id => possible returned quantities
     * @return \Generator<array<int, float>>
     */
    protected function combinations(array $options): \Generator
    {
        $ids = array_keys($options);

        if ($ids === []) {
            yield [];

            return;
        }

        $first = array_shift($ids);
        $rest = array_intersect_key($options, array_flip($ids));

        foreach ($options[$first] as $qty) {
            foreach ($this->combinations($rest) as $tail) {
                yield [$first => (float) $qty] + $tail;
            }
        }
    }

    protected function issue(bool $full, bool $anyMarked, float $paid, float $total): string
    {
        $money = fn (float $v) => number_format($v, 2);

        return match (true) {
            $full && $paid > $total => "Customer paid {$money($paid)}, but with everything back only {$money($total)} is owed — did they keep an item, or is a refund due?",
            $full => "Courier collected {$money($paid)} on return, but the delivery charge on the order is {$money($total)}.",
            ! $anyMarked => "Partial delivery — mark which items came back. Courier collected {$money($paid)}.",
            default => "Courier collected {$money($paid)}, but the kept items with delivery come to {$money($total)}.",
        };
    }

    /**
     * Receives the parcel: optional delivery charge / discount correction
     * (needs a reason; only before a sale is posted to accounts), the
     * Kept/Returned split, then the order's status and payment status.
     * All back → Returned (payment Failed if nothing was paid); some kept
     * → Delivered with a partial fulfillment. One log entry with every
     * before/after value.
     *
     * @param  array<int, float|string>  $returned  item id => returned quantity
     *
     * @throws DomainException  a charges change without a reason
     * @throws \App\Exceptions\Inventory\InsufficientStockException
     */
    public function handle(Order $order, array $returned, ?float $shipping = null, ?float $discount = null, string $reason = ''): void
    {
        $order->loadMissing('items');

        if (! static::awaiting($order)) {
            throw new DomainException("Order #{$order->id} has no returned parcel waiting.");
        }

        $reason = trim($reason);
        $before = RecordOrderReturns::snapshot($order);
        $fromStatus = $order->status;

        $chargesChange = [];

        if (! $order->salePosted()) {
            if ($shipping !== null && abs($shipping - (float) $order->shipping_amount) > 0.004) {
                $chargesChange['shipping_amount'] = round(max(0.0, $shipping), 2);
            }

            if ($discount !== null && abs($discount - (float) $order->discount_amount) > 0.004) {
                $itemsTotal = (float) $order->items->sum(fn (OrderItem $i) => $i->is_gift ? 0 : (float) $i->total_amount);
                $chargesChange['discount_amount'] = round(min(max(0.0, $discount), $itemsTotal), 2);
            }
        }

        if ($chargesChange !== [] && $reason === '') {
            throw new DomainException('Say why the delivery charge or discount is changing — it goes in the order log.');
        }

        $allBack = $order->items->every(fn (OrderItem $i) => (float) ($returned[$i->id] ?? 0) >= (float) $i->quantity);

        DB::transaction(function () use ($order, $returned, $chargesChange, $allBack, $fromStatus) {
            if ($chargesChange !== []) {
                $order->update($chargesChange);
            }

            // Courier said full return, but the customer kept something — it's
            // really a partial delivery.
            if ($fromStatus === OrderStatus::RETURNING && ! $allBack) {
                $order->update(['status' => OrderStatus::DELIVERED, 'fulfillment_status' => FulfillmentStatus::PARTIAL]);
            }

            $this->recordReturns->handle($order, $returned);
            $order->refresh();

            // Stock: RecordOrderReturns put the returned units back (into their
            // batches, inventory on) and synced bookings to the final status.
            $order->forceFill([
                'courier_status' => CourierStatus::RETURNED,
                'courier_status_updated_at' => now(),
                'payment_status' => $allBack && (float) $order->paid_amount <= 0 ? PaymentStatus::FAILED : $order->payment_status,
            ])->save();
        });

        $order->refresh()->load('items');

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->withProperties(['changes' => array_filter([
                'reason' => $reason !== '' ? $reason : null,
                'before' => $before,
                'after'  => RecordOrderReturns::snapshot($order),
            ])])
            ->log("Return received for Order #{$order->id}" . ($reason !== '' ? " — {$reason}" : ''));
    }
}
