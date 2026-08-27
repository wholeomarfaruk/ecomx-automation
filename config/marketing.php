<?php

return [

    // How long a marketing_sessions row stays "active" — a request after
    // this much inactivity starts a new session instead of reusing the old one.
    'session_timeout_minutes' => env('MARKETING_SESSION_TIMEOUT_MINUTES', 30),

    // How long an mk_first_touch/mk_last_touch attribution cookie survives —
    // deliberately independent of the device fingerprint's 10-year lifetime.
    'attribution_lifetime_days' => env('MARKETING_ATTRIBUTION_LIFETIME_DAYS', 90),

    'tracking' => [
        'page_views' => env('MARKETING_TRACK_PAGE_VIEWS', true),
        'anonymous' => env('MARKETING_TRACK_ANONYMOUS', true),
    ],

    'gtm' => [
        'enabled' => env('MARKETING_GTM_ENABLED', false),
        'container_id' => env('GTM_CONTAINER_ID'),
    ],

    // Server-side destinations to queue a delivery attempt for on every
    // tracked event. Only 'meta' has an adapter registered so far (see
    // App\Marketing\Destinations\DestinationRegistry) — listing an
    // unregistered key here would fail loudly when the job runs, so keep
    // this in sync with the registry.
    'destinations' => array_filter(explode(',', (string) env('MARKETING_SERVER_DESTINATIONS', 'meta'))),

];
