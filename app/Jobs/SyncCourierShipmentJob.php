<?php

namespace App\Jobs;

use App\Courier\CourierManager;
use App\Models\CourierShipment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Polls a courier's API for a shipment's latest status — the fallback path
 * when a courier has no webhook (or its webhook is unreliable). See
 * SyncCourierShipmentsCommand for the scheduler entry that dispatches this
 * for every active, non-final shipment.
 */
class SyncCourierShipmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $courierShipmentId,
    ) {
    }

    public function handle(CourierManager $courierManager): void
    {
        $shipment = CourierShipment::findOrFail($this->courierShipmentId);

        $courierManager->syncTracking($shipment);
    }
}
