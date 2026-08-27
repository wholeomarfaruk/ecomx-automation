<?php

namespace App\Marketing\Attribution;

use App\Marketing\Context\MarketingContext;
use Carbon\CarbonImmutable;

final class AttributionService
{
    public function __construct(
        private readonly FirstTouchResolver $firstTouchResolver,
        private readonly LastTouchResolver $lastTouchResolver,
    ) {}

    public function resolve(
        MarketingContext $context,
    ): MarketingAttribution {
        $currentTouch = $this->resolveCurrentTouch($context);

        return new MarketingAttribution(
            firstTouch: $this->firstTouchResolver->resolve(
                context: $context,
                currentTouch: $currentTouch,
            ),

            lastTouch: $this->lastTouchResolver->resolve(
                context: $context,
                currentTouch: $currentTouch,
            ),

            currentTouch: $currentTouch,
        );
    }

    public function cookies(
        MarketingAttribution $attribution,
    ): array {
        $cookies = [];

        if ($attribution->firstTouch) {
            $cookies['mk_first_touch'] = json_encode($attribution->firstTouch->toArray());
        }

        if ($attribution->lastTouch) {
            $cookies['mk_last_touch'] = json_encode($attribution->lastTouch->toArray());
        }

        return $cookies;
    }

    private function resolveCurrentTouch(
        MarketingContext $context,
    ): AttributionTouch {
        $tracking = $context->trackingParameters;

        return new AttributionTouch(
            source: $tracking['utm_source'] ?? null,
            medium: $tracking['utm_medium'] ?? null,
            campaign: $tracking['utm_campaign'] ?? null,
            term: $tracking['utm_term'] ?? null,
            content: $tracking['utm_content'] ?? null,

            fbclid: $tracking['fbclid'] ?? null,
            gclid: $tracking['gclid'] ?? null,
            ttclid: $tracking['ttclid'] ?? null,

            referrer: $context->referrer,
            landingUrl: $context->pageUrl,

            capturedAt: CarbonImmutable::now(),
        );
    }
}
