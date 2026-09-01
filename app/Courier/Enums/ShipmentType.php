<?php

namespace App\Courier\Enums;

enum ShipmentType: string
{
    case NORMAL = 'normal';
    case EXCHANGE = 'exchange';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal Delivery',
            self::EXCHANGE => 'Exchange Parcel',
        };
    }
}
