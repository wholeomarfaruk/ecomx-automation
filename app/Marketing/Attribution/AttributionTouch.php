<?php

namespace App\Marketing\Attribution;

use Carbon\CarbonInterface;

final readonly class AttributionTouch
{
    public function __construct(
        public ?string $source = null,
        public ?string $medium = null,
        public ?string $campaign = null,
        public ?string $term = null,
        public ?string $content = null,

        public ?string $fbclid = null,
        public ?string $gclid = null,
        public ?string $ttclid = null,

        public ?string $referrer = null,
        public ?string $landingUrl = null,

        public ?CarbonInterface $capturedAt = null,
    ) {}

    public function hasMarketingData(): bool
    {
        return filled($this->source)
            || filled($this->medium)
            || filled($this->campaign)
            || filled($this->fbclid)
            || filled($this->gclid)
            || filled($this->ttclid);
    }

    public static function fromStored(array|string|null $stored): ?self
    {
        if (is_string($stored)) {
            $stored = json_decode($stored, true);
        }

        if (! is_array($stored)) {
            return null;
        }

        return new self(
            source: $stored['source'] ?? null,
            medium: $stored['medium'] ?? null,
            campaign: $stored['campaign'] ?? null,
            term: $stored['term'] ?? null,
            content: $stored['content'] ?? null,

            fbclid: $stored['fbclid'] ?? null,
            gclid: $stored['gclid'] ?? null,
            ttclid: $stored['ttclid'] ?? null,

            referrer: $stored['referrer'] ?? null,
            landingUrl: $stored['landing_url'] ?? null,

            capturedAt: isset($stored['captured_at'])
                ? \Carbon\CarbonImmutable::parse($stored['captured_at'])
                : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'source' => $this->source,
            'medium' => $this->medium,
            'campaign' => $this->campaign,
            'term' => $this->term,
            'content' => $this->content,
            'fbclid' => $this->fbclid,
            'gclid' => $this->gclid,
            'ttclid' => $this->ttclid,
            'referrer' => $this->referrer,
            'landing_url' => $this->landingUrl,
            'captured_at' => $this->capturedAt?->toISOString(),
        ], fn ($value) => $value !== null);
    }
}
