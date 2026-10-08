<?php

namespace App\Courier\Contracts;

use App\Models\CourierShipment;

/**
 * Opt-in for drivers whose courier has a public, customer-facing tracking
 * page. Used for the order page's "Tracking link" and the {tracking_url}
 * SMS placeholder — see CourierShipment::trackingUrl().
 */
interface HasPublicTrackingUrl
{
    public static function publicTrackingUrl(CourierShipment $shipment): ?string;
}
