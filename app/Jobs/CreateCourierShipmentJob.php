<?php

namespace App\Jobs;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Books a shipment off the request/response cycle — a courier's API call
 * during a web request would leave the admin UI hanging on a slow gateway.
 */
class CreateCourierShipmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $orderId,
        public string $courierKey,
        public ShipmentRequest $request,
    ) {
    }

    public function handle(CourierManager $courierManager): void
    {
        $order = Order::findOrFail($this->orderId);

        $courierManager->createShipment($order, $this->courierKey, $this->request);
    }
}
