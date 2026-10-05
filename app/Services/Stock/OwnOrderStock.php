<?php

namespace App\Services\Stock;

use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Order stock while the Inventory module is OFF — the plain way: each line
 * takes its units straight off the product's (simple) or variant's own
 * stock_quantity and gives them back the same way. No ledger rows, no
 * batches, no warehouses. order_items.stock_deducted remembers how much a
 * line currently holds, which makes every call idempotent and lets a
 * cancel/return give back exactly what was taken — never more.
 *
 * Driven by the order's status, so every place that changes status (order
 * page, Orders table, courier webhook, Create/Bulk order, order editor,
 * returns, deletion, POS) only has to call sync():
 *
 *  - confirmed / processing / shipped / delivered / returning
 *    / partially returned / refunded → quantity − returned_quantity is out of stock
 *  - pending / cancelled / returned → nothing is
 *
 * Stock given back on cancel/return honours Inventory → "restock on
 * cancel/return" (default on); with it off, the units are written off.
 */
class OwnOrderStock
{
    /**
     * Brings every line of the order to what its status says should be out
     * of stock.
     *
     * @throws InsufficientStockException  when a line can't be taken and negative stock isn't allowed
     */
    public function sync(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items');
            $status = $order->status;

            foreach ($order->items as $item) {
                $this->syncItem($item, $status);
            }
        });
    }

    /** One line to its status target. */
    public function syncItem(OrderItem $item, ?OrderStatus $status = null): void
    {
        if (! $item->product_id) {
            return; // combos carry no stock of their own
        }

        $status ??= $item->order->status;
        $this->moveTo($item, $this->target($item, $status), $status);
    }

    /** Gives back everything a line holds — before it's edited away or deleted. */
    public function releaseItem(OrderItem $item): void
    {
        if ($item->product_id && (float) $item->stock_deducted > 0) {
            $this->moveTo($item, 0.0, null);
        }
    }

    /** Whether any line of this order currently holds stock. */
    public function holds(Order $order): bool
    {
        return $order->items()->where('stock_deducted', '>', 0)->exists();
    }

    /** How much of this line should be out of stock in this status. */
    public function target(OrderItem $item, OrderStatus $status): float
    {
        $keeps = match ($status) {
            OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED,
            OrderStatus::RETURNING, OrderStatus::PARTIALLY_RETURNED,
            // A refund is money — goods that came back are in returned_quantity
            // (or the order is Returned), the rest stayed with the customer.
            OrderStatus::REFUNDED => true,
            default => false,
        };

        return $keeps ? max(0.0, (float) $item->quantity - (float) $item->returned_quantity) : 0.0;
    }

    /**
     * Moves one line's holding to $target: takes the shortfall off stock
     * (checked unless negative stock is allowed), or gives the surplus back
     * (unless restocking on cancel/return is switched off).
     */
    protected function moveTo(OrderItem $item, float $target, ?OrderStatus $status): void
    {
        DB::transaction(function () use ($item, $target, $status) {
            $line = OrderItem::query()->whereKey($item->id)->lockForUpdate()->first();

            if (! $line) {
                return;
            }

            $held = (float) $line->stock_deducted;
            $delta = round($target - $held, 3);

            if (abs($delta) < 0.0005) {
                return;
            }

            $writeOff = $delta < 0 && $status !== null && ! (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');

            if (! $writeOff) {
                $this->adjust($line, -$delta);
            }

            $line->update(['stock_deducted' => $target]);
            $item->forceFill(['stock_deducted' => $target])->syncOriginal();
        });
    }

    /**
     * Adds $change to the line's product/variant stock_quantity (negative to
     * take). A simple product's stock_status follows the count across zero —
     * what the storefront reads for simple products.
     *
     * @throws InsufficientStockException
     */
    protected function adjust(OrderItem $line, float $change): void
    {
        $row = $line->variant_id
            ? ProductVariant::withTrashed()->whereKey($line->variant_id)->lockForUpdate()->first()
            : Product::withTrashed()->whereKey($line->product_id)->lockForUpdate()->first();

        if (! $row) {
            return; // product/variant gone — nothing to give back to
        }

        $before = (float) $row->stock_quantity;
        $after = round($before + $change, 3);

        if ($change < 0 && $after < 0 && ! (bool) Setting::get('allow_negative_stock', false, 'inventory')) {
            throw InsufficientStockException::forProduct($line->product_name ?: 'This product', abs($change), max(0.0, $before));
        }

        $changes = ['stock_quantity' => $after];

        if (! $line->variant_id) {
            if ($after <= 0 && $row->stock_status === 'in_stock') {
                $changes['stock_status'] = 'out_of_stock';
            } elseif ($after > 0 && $row->stock_status === 'out_of_stock') {
                $changes['stock_status'] = 'in_stock';
            }
        }

        $row->update($changes);
    }
}
