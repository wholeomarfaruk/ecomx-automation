<?php

namespace App\Actions\Sales\Exchanges;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\ExchangeStatus;
use App\Models\CourierShipment;
use App\Models\OrderExchange;

/**
 * An exchange parcel's courier update (webhook, tracking sync, cancel) moves
 * its exchange — forward only, never to Completed/Failed on its own (see
 * ExchangeStatus::afterCourier()). A parcel cancelled at the courier unlinks
 * it so the exchange can be booked again.
 */
class SyncExchangeFromCourier
{
    public function handle(CourierShipment $shipment, CourierStatus $status): void
    {
        $exchange = OrderExchange::where('courier_shipment_id', $shipment->id)->first();

        if (! $exchange) {
            return;
        }

        $next = $exchange->status->afterCourier($status);

        if (! $next) {
            return;
        }

        $exchange->update([
            'status'              => $next,
            'courier_shipment_id' => $next === ExchangeStatus::REQUESTED ? null : $exchange->courier_shipment_id,
        ]);

        activity('sales')->causedBy(auth()->user())->performedOn($exchange->order)->event('updated')
            ->withProperties(['changes' => ['exchange' => $exchange->number, 'courier' => $status->value, 'status' => $next->value]])
            ->log("Exchange #{$exchange->number}: {$next->label()} (courier: {$status->label()})");
    }
}
