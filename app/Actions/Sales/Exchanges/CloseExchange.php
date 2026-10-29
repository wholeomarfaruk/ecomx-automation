<?php

namespace App\Actions\Sales\Exchanges;

use App\Actions\Sales\PackOrderItem;
use App\Actions\Sales\RecordOrderReturns;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\ExchangeStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\OrderExchange;
use App\Models\OrderItem;
use App\Services\StockService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * The three ways an exchange ends:
 *
 *  complete() — the old goods are back at the shop: they're recorded as
 *               returned and restocked (RecordOrderReturns); the credit the
 *               order already gave for them simply moves from "pending
 *               exchange" to "returned". Also for an in-shop exchange with
 *               no parcel at all.
 *  fail()     — the customer refused: the new goods came back (returned +
 *               restocked), the old ones stay sold; the exchange charge is
 *               dropped unless the customer paid it anyway.
 *  cancel()   — dropped before any parcel left: the new lines are removed
 *               and their stock given back, the charge removed.
 *
 * An exchange's returns never make the order look partially delivered —
 * the fulfillment follows only real returns (syncFulfillment()).
 */
class CloseExchange
{
    public function __construct(protected StockService $stockService) {}

    public function complete(OrderExchange $exchange): void
    {
        $this->guardActive($exchange);

        if (! $exchange->status->canComplete()) {
            throw new DomainException("Exchange #{$exchange->number}'s parcel hasn't reached the customer yet — the old item can't be back.");
        }

        DB::transaction(function () use ($exchange) {
            $order = $exchange->order()->with('items')->firstOrFail();
            $back = $exchange->returnItems()->pluck('quantity', 'order_item_id');

            $exchange->update(['status' => ExchangeStatus::COMPLETED, 'completed_at' => now()]);

            app(RecordOrderReturns::class)->handle($order, $this->returnedWith($order, $back->all()));

            $this->syncFulfillment($order->fresh('items'));
            $this->log($exchange, 'completed — old item received and restocked');
        });
    }

    public function fail(OrderExchange $exchange, bool $keepCharge): void
    {
        $this->guardActive($exchange);

        if (! $exchange->status->canFail()) {
            throw new DomainException($exchange->status === ExchangeStatus::BOOKED
                ? "Exchange #{$exchange->number}'s parcel isn't picked up yet — cancel the parcel (or remove it) and cancel the exchange instead."
                : "Exchange #{$exchange->number} never went out — cancel it instead.");
        }

        DB::transaction(function () use ($exchange, $keepCharge) {
            $order = $exchange->order()->with('items')->firstOrFail();
            $newBack = $exchange->newItems()->get()->mapWithKeys(fn (OrderItem $i) => [$i->id => (float) $i->quantity]);

            $exchange->update(['status' => ExchangeStatus::FAILED, 'failed_at' => now()]);

            if (! $keepCharge) {
                $exchange->removeCharges();
            }

            app(RecordOrderReturns::class)->handle($order, $this->returnedWith($order, $newBack->all()));

            $this->syncFulfillment($order->fresh('items'));
            $this->log($exchange, 'failed — new item came back' . ($keepCharge ? ', exchange charge kept' : ''));
        });
    }

    public function cancel(OrderExchange $exchange): void
    {
        if ($exchange->status !== ExchangeStatus::REQUESTED) {
            throw new DomainException("Exchange #{$exchange->number} is {$exchange->status->label()} — only one that hasn't gone out can be cancelled.");
        }

        $live = $exchange->shipment && ! in_array($exchange->shipment->statusEnum(), [CourierStatus::CANCELLED], true);

        if ($live) {
            throw new DomainException('Cancel its courier parcel first.');
        }

        DB::transaction(function () use ($exchange) {
            foreach ($exchange->newItems()->with('batchAllocations')->get() as $item) {
                // Packed from a batch (Inventory module) — back into that batch first.
                if ($item->batchAllocations->isNotEmpty()) {
                    app(PackOrderItem::class)->unpack($item);
                }

                $this->stockService->reverseItemForDeletion($item->fresh());
                $item->delete();
            }

            $exchange->removeCharges();
            $exchange->update(['status' => ExchangeStatus::CANCELLED, 'cancelled_at' => now()]);

            $order = $exchange->order()->firstOrFail();
            $order->recalculateTotals();
            $order->refresh()->syncPaymentStatus();

            $this->log($exchange, 'cancelled');
        });
    }

    protected function guardActive(OrderExchange $exchange): void
    {
        if (! $exchange->status->isActive()) {
            throw new DomainException("Exchange #{$exchange->number} is already {$exchange->status->label()}.");
        }
    }

    /**
     * Every line's returned quantity as saved now (lost units included),
     * plus $extra — RecordOrderReturns takes the full picture.
     *
     * @param  array<int, float|string>  $extra  order item id => quantity coming back now
     * @return array<int, float>
     */
    protected function returnedWith(Order $order, array $extra): array
    {
        return $order->items->mapWithKeys(fn (OrderItem $item) => [
            $item->id => min((float) $item->quantity, (float) $item->returned_quantity + (float) ($extra[$item->id] ?? 0)),
        ])->all();
    }

    /**
     * A delivered order is fully fulfilled unless something came back that
     * an exchange doesn't explain (old goods swapped by a completed exchange,
     * new goods of a failed one).
     */
    public function syncFulfillment(Order $order): void
    {
        if ($order->status !== OrderStatus::DELIVERED) {
            return;
        }

        $exchanges = $order->exchanges()->with('returnItems')->whereIn('status', [ExchangeStatus::COMPLETED, ExchangeStatus::FAILED])->get();
        $swapped = $exchanges->where('status', ExchangeStatus::COMPLETED)->flatMap->returnItems
            ->groupBy('order_item_id')->map(fn ($rows) => (float) $rows->sum('quantity'));
        $failedIds = $exchanges->where('status', ExchangeStatus::FAILED)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $realReturns = $order->items->contains(function (OrderItem $item) use ($swapped, $failedIds) {
            $explained = $item->exchange_id && in_array((int) $item->exchange_id, $failedIds, true) ? (float) $item->quantity : (float) ($swapped[$item->id] ?? 0);

            return (float) $item->returned_quantity - $explained > 0.0005;
        });

        $fulfillment = $realReturns ? FulfillmentStatus::PARTIAL : FulfillmentStatus::FULFILLED;

        if ($order->fulfillment_status !== $fulfillment) {
            $order->update(['fulfillment_status' => $fulfillment]);
        }
    }

    protected function log(OrderExchange $exchange, string $what): void
    {
        activity('sales')->causedBy(auth()->user())->performedOn($exchange->order)->event('updated')
            ->log("Exchange #{$exchange->number} {$what}");
    }
}
