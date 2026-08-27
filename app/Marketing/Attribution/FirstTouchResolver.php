<?php

namespace App\Marketing\Attribution;

use App\Marketing\Context\MarketingContext;

final class FirstTouchResolver
{
    public function resolve(
        MarketingContext $context,
        AttributionTouch $currentTouch,
    ): ?AttributionTouch {
        $stored = AttributionTouch::fromStored(
            $context->trackingCookies['mk_first_touch'] ?? null,
        );

        if ($stored) {
            return $stored;
        }

        if ($currentTouch->hasMarketingData()) {
            return $currentTouch;
        }

        return null;
    }
}
