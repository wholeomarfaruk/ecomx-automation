<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Data\MarketingEventData;

/**
 * The `meta` block of a dataLayer push: everything a GTM Meta Pixel tag
 * needs, built by the same code that builds the Conversions API payload,
 * so the browser event and its server twin carry identical data:
 *
 *   meta.event_name  → the Pixel's event name (PageView, ViewContent, …)
 *   meta.custom_data → object properties (value, currency, content_ids, contents, …)
 *   meta.user_data   → advanced matching, already normalized + SHA-256 hashed
 *   marketing.event_id (outside this block) → the Pixel's Event ID, for deduplication
 *
 * Hashed on the server, so no raw email/phone/name ever enters the
 * dataLayer (which every GTM tag can read). IP and user agent are left out —
 * the Pixel collects those itself in the browser.
 */
final class MetaBrowserData
{
    public function __construct(
        private readonly MetaPayloadBuilder $payloadBuilder,
    ) {}

    /** @return array{event_name: string, custom_data?: array, user_data?: array} */
    public function for(MarketingEventData $data): array
    {
        $userData = $this->payloadBuilder->matchKeys($data->identity);

        // Conversions API takes a list of external ids; the Pixel takes one
        // string — the most specific (customer before device) goes.
        if (isset($userData['external_id'])) {
            $userData['external_id'] = $userData['external_id'][0];
        }

        // The Pixel reads these cookies itself; they're here too so every
        // value the server sends is selectable in GTM as well.
        $userData += array_filter([
            'fbp' => $data->context->trackingCookies[MetaBrowserCookies::FBP] ?? null,
            'fbc' => $data->context->trackingCookies[MetaBrowserCookies::FBC] ?? null,
        ]);

        return array_filter([
            'event_name' => $data->event->eventName(),
            'custom_data' => $this->payloadBuilder->customData($data->event),
            'user_data' => $userData,
        ], fn ($value) => $value !== []);
    }
}
