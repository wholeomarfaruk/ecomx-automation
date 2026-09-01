<?php

namespace App\Courier\Enums;

/**
 * Not every courier's API can do everything — Sundarban/SA Paribahan may
 * not expose the same tracking/webhook surface Pathao or SteadFast do. A
 * driver declares which of these it supports via its static meta(), and
 * the admin UI hides actions/tabs the current courier can't perform
 * instead of letting them fail against a driver that doesn't implement them.
 */
enum CourierCapability: string
{
    case SHIPMENT_CREATE = 'shipment_create';
    case SHIPMENT_CANCEL = 'shipment_cancel';
    case TRACKING = 'tracking';
    case STATUS_SYNC = 'status_sync';
    case RATE_CALCULATION = 'rate_calculation';
    case COD = 'cod';
    case RETURN = 'return';
    case PICKUP_REQUEST = 'pickup_request';
    case WEBHOOK = 'webhook';
    case LABEL = 'label';
    case BALANCE = 'balance';
    case EXCHANGE = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::SHIPMENT_CREATE => 'Create Shipment',
            self::SHIPMENT_CANCEL => 'Cancel Shipment',
            self::TRACKING => 'Tracking',
            self::STATUS_SYNC => 'Status Sync',
            self::RATE_CALCULATION => 'Rate Calculation',
            self::COD => 'Cash on Delivery',
            self::RETURN => 'Return Handling',
            self::PICKUP_REQUEST => 'Pickup Request',
            self::WEBHOOK => 'Webhook',
            self::LABEL => 'Shipping Label',
            self::BALANCE => 'Balance Check',
            self::EXCHANGE => 'Exchange Parcel',
        };
    }
}
