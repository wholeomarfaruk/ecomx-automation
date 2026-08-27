<?php

namespace App\Marketing\Data;

use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Identity\MarketingIdentity;

final readonly class MarketingEventData
{
    public function __construct(
        public EventContract $event,
        public MarketingContext $context,
        public MarketingIdentity $identity,
        public MarketingAttribution $attribution,
    ) {}
}
