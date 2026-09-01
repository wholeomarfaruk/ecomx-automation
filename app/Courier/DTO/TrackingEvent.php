<?php

namespace App\Courier\DTO;

use App\Enums\Sales\CourierStatus;
use Illuminate\Support\Carbon;

class TrackingEvent
{
    public function __construct(
        public CourierStatus $status,
        public ?string $rawStatus = null,
        public ?string $message = null,
        public ?string $location = null,
        public ?Carbon $eventAt = null,
        public array $rawData = [],
    ) {
    }
}
