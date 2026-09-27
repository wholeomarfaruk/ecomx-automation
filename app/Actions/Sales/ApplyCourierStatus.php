<?php

namespace App\Actions\Sales;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\Log;

/**
 * Moves an order along its own status flow as its courier shipment
 * progresses, so the admin doesn't have to mirror every courier update by
 * hand. Every courier update path (webhook, polling sync) goes through
 * handle(); booking a shipment goes through shipmentCreated().
 *
 *   shipment booked         -> order processing              (courier pending)
 *   picked up / in transit  -> order shipped
 *   delivered               -> order delivered, fulfilled
 *   partially delivered     -> order delivered, partial      (courier returning)
 *   returning               -> order returning               (full return only)
 *   returned                -> order returned, unfulfilled   (full return only)
 *
 * A partial delivery's leftover return (returning/returned after the order
 * is already delivered) only moves the courier status — the order stays
 * delivered with a partial fulfillment. Transitions only ever move forward
 * from the statuses listed in each rule, so a late or out-of-order courier
 * update can never pull an order backwards, and a closed order is never
 * touched. No accounting is posted here.
 */
class ApplyCourierStatus
{
    protected const BEFORE_SHIPPED = [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING];

    public function __construct(protected StockService $stockService) {}

    public function shipmentCreated(Order $order): void
    {
        if (in_array($order->status, [OrderStatus::PENDING, OrderStatus::CONFIRMED], true)) {
            $this->moveOrder($order, OrderStatus::PROCESSING);
        }
    }

    public function handle(CourierShipment $shipment, CourierStatus $status): void
    {
        $stored = $status === CourierStatus::PARTIAL_DELIVERED ? CourierStatus::RETURNING : $status;

        $shipment->update([
            'previous_status' => $shipment->status,
            'status' => $stored->value,
        ]);

        $order = $shipment->order;

        $order->forceFill([
            'courier_status' => $stored,
            'courier_status_updated_at' => now(),
        ])->save();

        if ($order->status->isClosed()) {
            return;
        }

        $inFlight = [...self::BEFORE_SHIPPED, OrderStatus::SHIPPED];

        match ($status) {
            CourierStatus::PICKED_UP, CourierStatus::IN_TRANSIT, CourierStatus::OUT_FOR_DELIVERY => $this->moveOrderFrom($order, self::BEFORE_SHIPPED, OrderStatus::SHIPPED),
            CourierStatus::DELIVERED => $this->moveOrderFrom($order, [...$inFlight, OrderStatus::RETURNING], OrderStatus::DELIVERED, FulfillmentStatus::FULFILLED),
            CourierStatus::PARTIAL_DELIVERED => $this->moveOrderFrom($order, $inFlight, OrderStatus::DELIVERED, FulfillmentStatus::PARTIAL),
            CourierStatus::RETURNING => $this->moveOrderFrom($order, $inFlight, OrderStatus::RETURNING),
            CourierStatus::RETURNED => $this->moveOrderFrom($order, [...$inFlight, OrderStatus::RETURNING], OrderStatus::RETURNED, FulfillmentStatus::UNFULFILLED),
            default => null,
        };
    }

    /**
     * @param  array<int, OrderStatus>  $from
     */
    protected function moveOrderFrom(Order $order, array $from, OrderStatus $to, ?FulfillmentStatus $fulfillment = null): void
    {
        if (in_array($order->status, $from, true)) {
            $this->moveOrder($order, $to, $fulfillment);
        }
    }

    /**
     * Same booking side effects as a manual status change from OrderDetail —
     * book on entering the bookable group, release on leaving it. A booking
     * shortfall is logged rather than thrown: the courier really did move
     * the parcel, so the status change must stand either way.
     */
    protected function moveOrder(Order $order, OrderStatus $to, ?FulfillmentStatus $fulfillment = null): void
    {
        $from = $order->status;

        $order->status = $to;

        if ($fulfillment) {
            $order->fulfillment_status = $fulfillment;
        }

        $order->save();

        if (! (bool) Setting::get('book_on_order_confirm', true, 'inventory')) {
            return;
        }

        if (! $from->isBookable() && $to->isBookable()) {
            try {
                $this->stockService->bookOrder($order->load('items'));
            } catch (InsufficientStockException $e) {
                Log::warning("Order #{$order->id}: stock booking skipped on courier status change — {$e->getMessage()}");
            }
        } elseif ($from->isBookable() && ! $to->isBookable()) {
            $this->stockService->releaseBooking($order->load('items'), $to->bookingReleaseType());
        }
    }
}
