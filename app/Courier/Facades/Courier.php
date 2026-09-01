<?php

namespace App\Courier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Courier\DTO\ShipmentResponse createShipment(\App\Models\Order $order, string $courierKey, \App\Courier\DTO\ShipmentRequest $request)
 * @method static \App\Courier\DTO\CourierResponse cancelShipment(\App\Models\CourierShipment $shipment)
 * @method static \App\Courier\DTO\TrackingResponse syncTracking(\App\Models\CourierShipment $shipment)
 * @method static \App\Courier\DTO\RateResponse calculateRate(string $courierKey, \App\Courier\DTO\RateRequest $request)
 * @method static \App\Courier\DTO\CourierResponse balance(string $courierKey)
 * @method static \App\Courier\DTO\CourierResponse test(string $courierKey)
 * @method static array status(string $courierKey)
 * @method static array installedCouriers()
 * @method static \App\Courier\Contracts\CourierDriverInterface driverFor(string $key)
 *
 * @see \App\Courier\CourierManager
 */
class Courier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'courier.manager';
    }
}
