<?php

namespace App\Marketing\Attribution;

final readonly class MarketingAttribution
{
    public function __construct(
        public ?AttributionTouch $firstTouch = null,
        public ?AttributionTouch $lastTouch = null,
        public ?AttributionTouch $currentTouch = null,
    ) {}

    public function toArray(): array
    {
        return [
            'first_touch' => $this->firstTouch?->toArray(),
            'last_touch' => $this->lastTouch?->toArray(),
            'current_touch' => $this->currentTouch?->toArray(),
        ];
    }
}
