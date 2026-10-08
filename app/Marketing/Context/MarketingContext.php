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
        // Livewire actions (AddToCart, placing an order) arrive as a
        // background POST to the Livewire update endpoint, so fullUrl() is
        // that endpoint, not the page. The browser's Referer on that request
        // IS the page; the page's own referrer isn't known from here.
        $isLivewire = $request->hasHeader('X-Livewire');
        $referer = $request->headers->get('referer');

        return new self(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            acceptLanguage: $request->header('Accept-Language'),
            host: $request->getHost(),
            pageUrl: $isLivewire ? ($referer ?: $request->fullUrl()) : $request->fullUrl(),
            referrer: $isLivewire ? null : $referer,
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
        $query = fn (string $key) => self::queryValue($request, $key);

        $parameters = [
            'utm_source' => $query('utm_source'),
            'utm_medium' => $query('utm_medium'),
            // Explicit name first, then the id params ad platforms send when
            // there's no utm_campaign: utm_id (TikTok/Meta auto-UTMs, GA4),
            // campaign_id (Meta {{campaign.id}} templates), gad_campaignid
            // (Google Ads auto-tagging, which sends no UTMs at all).
            'utm_campaign' => $query('utm_campaign') ?? $query('utm_id') ?? $query('campaign_id') ?? $query('gad_campaignid'),
            'utm_term' => $query('utm_term'),
            'utm_content' => $query('utm_content'),
            'fbclid' => $query('fbclid'),
            // gbraid/wbraid replace gclid on iOS traffic; dclid is Display & Video 360.
            'gclid' => $query('gclid') ?? $query('gbraid') ?? $query('wbraid') ?? $query('dclid'),
            'ttclid' => $query('ttclid'),
        ];

        // Click ids say which platform sent the visit even when the ad link
        // has no utm_source. Only when it has none — an explicit source wins,
        // and its medium is never mixed with an inferred one.
        if ($parameters['utm_source'] === null) {
            [$parameters['utm_source'], $inferredMedium] = self::inferredSource($request, $parameters);
            $parameters['utm_medium'] ??= $inferredMedium;
        }

        return collect($parameters)
            ->map(fn ($value, $key) => self::normalize($key, $value))
            ->filter()
            ->all();
    }

    /** @return array{0: ?string, 1: ?string} [source, medium] */
    private static function inferredSource(Request $request, array $parameters): array
    {
        $googleAds = $parameters['gclid'] !== null
            || self::queryValue($request, 'gad_campaignid') !== null
            || self::queryValue($request, 'gad_source') !== null;

        if ($googleAds) {
            // YouTube ads run through Google Ads with the same click ids —
            // the referrer is the only tell.
            return [self::referredBy($request, ['youtube.com', 'youtu.be']) ? 'youtube' : 'google', 'cpc'];
        }

        if ($parameters['ttclid'] !== null) {
            return ['tiktok', 'paid'];
        }

        // Facebook/Instagram add fbclid to every outbound link, organic posts
        // included, so it names the source but doesn't prove a paid click.
        if ($parameters['fbclid'] !== null) {
            return [self::referredBy($request, ['instagram.com']) ? 'instagram' : 'facebook', null];
        }

        return [null, null];
    }

    private static function referredBy(Request $request, array $domains): bool
    {
        $host = mb_strtolower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));

        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    private static function queryValue(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value) || ($value = trim($value)) === '' || self::isUnreplacedMacro($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Ad macros arrive literally when a link is opened from an ad preview or
     * the macro is mistyped — Meta "{{campaign.name}}", Google "{campaignid}",
     * TikTok "__CAMPAIGN_NAME__" — and aren't real values.
     */
    public static function isUnreplacedMacro(string $value): bool
    {
        return str_contains($value, '{') || preg_match('/__[A-Z][A-Z_]*__/', $value) === 1;
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
