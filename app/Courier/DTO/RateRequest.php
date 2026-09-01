<?php

namespace App\Courier\DTO;

class RateRequest
{
    public function __construct(
        public ?string $originCity = null,
        public ?string $destinationCity = null,
        public ?string $destinationZone = null,
        public float $weight = 0,
        public float $codAmount = 0,
    ) {
    }
}
