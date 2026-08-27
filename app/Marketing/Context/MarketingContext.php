<?php

namespace App\Marketing\Context;

use App\Marketing\Attribution\MarketingAttribution;
use App\Models\Marketing\MarketingUtmRule;
use Illuminate\Http\Request;

final readonly class MarketingContext
{
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $acceptLanguage = null,
        public ?string $host = null,
        public ?string $pageUrl = null,
        public ?string $referrer = null,
        public ?string $deviceFingerprint = null,
        public ?string $sessionId = null,
        public array $trackingCookies = [],
        public array $trackingParameters = [],
        public mixed $customer = null,
        public mixed $user = null,
        public ?MarketingAttribution $attribution = null,
    ) {}

    public static function fromRequest(
        Request $request,
        ?string $deviceFingerprint = null,
        mixed $customer = null,
        mixed $user = null,
    ): self {
        return new self(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            acceptLanguage: $request->header('Accept-Language'),
            host: $request->getHost(),
            pageUrl: $request->fullUrl(),
            referrer: $request->headers->get('referer'),
            deviceFingerprint: $deviceFingerprint,
            sessionId: $request->hasSession() ? $request->session()->getId() : null,
            trackingCookies: [
                '_fbp' => $request->cookie('_fbp'),
                '_fbc' => $request->cookie('_fbc'),
                'mk_first_touch' => $request->cookie('mk_first_touch'),
                'mk_last_touch' => $request->cookie('mk_last_touch'),
            ],
            trackingParameters: self::trackingParameters($request),
            customer: $customer,
            user: $user,
        );
    }

    public function withAttribution(?MarketingAttribution $attribution): self
    {
        return new self(
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            acceptLanguage: $this->acceptLanguage,
            host: $this->host,
            pageUrl: $this->pageUrl,
            referrer: $this->referrer,
            deviceFingerprint: $this->deviceFingerprint,
            sessionId: $this->sessionId,
            trackingCookies: $this->trackingCookies,
            trackingParameters: $this->trackingParameters,
            customer: $this->customer,
            user: $this->user,
            attribution: $attribution,
        );
    }

    // Normalized so 'Facebook'/'facebook'/'FACEBOOK' group as one campaign
    // source instead of three in reports. utm_campaign/term/content are left
    // as-is — those are often meaningfully case-sensitive creative/campaign
    // identifiers, not a small fixed vocabulary like source/medium.
    private const NORMALIZED_KEYS = ['utm_source', 'utm_medium'];

    private static function trackingParameters(Request $request): array
    {
        $keys = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'fbclid',
            'gclid',
            'ttclid',
        ];

        return collect($keys)
            ->mapWithKeys(fn ($key) => [
                $key => self::normalize($key, $request->query($key)),
            ])
            ->filter()
            ->all();
    }

    private static function normalize(string $key, mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        // Admin-configured overrides (Tracking Settings → UTM Rules) run
        // first — e.g. "fb" → "facebook" — then the built-in case-fold
        // below handles the remaining source/medium casing variance.
        $value = MarketingUtmRule::normalize($key, $value);

        if (! in_array($key, self::NORMALIZED_KEYS, true)) {
            return $value;
        }

        return strtolower(trim($value));
    }
}
