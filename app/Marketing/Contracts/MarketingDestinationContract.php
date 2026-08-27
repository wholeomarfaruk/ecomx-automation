<?php

namespace App\Marketing\Contracts;

use App\Marketing\Context\MarketingContext;
use App\Marketing\DTOs\DestinationResult;

interface MarketingDestinationContract
{
    public function key(): string;

    public function send(
        EventContract $event,
        MarketingContext $context,
    ): DestinationResult;
}
