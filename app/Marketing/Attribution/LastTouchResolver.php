<?php

namespace App\Marketing\Attribution;

use App\Marketing\Context\MarketingContext;

final class LastTouchResolver
{
    public function resolve(
        MarketingContext $context,
        AttributionTouch $currentTouch,
    ): ?AttributionTouch {
        if ($currentTouch->hasMarketingData()) {
            return $currentTouch;
        }

        return AttributionTouch::fromStored(
            $context->trackingCookies['mk_last_touch'] ?? null,
        );
    }
}
