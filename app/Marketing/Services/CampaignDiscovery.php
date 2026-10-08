<?php

namespace App\Marketing\Services;

use App\Marketing\Attribution\AttributionTouch;
use App\Marketing\Context\MarketingContext;
use App\Models\Marketing\MarketingAttribution;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Marketing\MarketingSource;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Registers marketing_campaigns rows automatically from the utm_campaign
 * values traffic actually arrives with, so Campaigns/Dashboard fill in on
 * their own — an admin only needs to add one by hand to name it or to
 * pre-register it before any traffic exists.
 *
 * One campaign per campaign_key (the raw utm_campaign value): the same
 * Meta campaign arrives as both utm_source=fb and utm_source=ig, and
 * reporting matches events on the key alone, so a second row per source
 * would double-count it.
 */
final class CampaignDiscovery
{
    public const PLATFORMS = [
        'meta' => 'Meta',
        'google' => 'Google',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'other' => 'Other / UTM',
    ];

    private const META_SOURCES = ['fb', 'ig', 'an', 'msg', 'meta', 'facebook', 'instagram', 'messenger', 'threads', 'audience_network'];

    private const GOOGLE_SOURCES = ['google', 'adwords', 'gads'];

    private const YOUTUBE_SOURCES = ['youtube', 'yt', 'youtube_ads'];

    /** Called for every recorded event — cached so a known campaign costs no query. */
    public function discover(?AttributionTouch $touch): void
    {
        if (! $touch || ! self::isUsableKey($touch->campaign)) {
            return;
        }

        $key = trim($touch->campaign);
        $cacheKey = 'marketing_campaign_known:'.md5(mb_strtolower($key));

        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            $this->ensure($key, self::platformFor(
                $touch->source,
                filled($touch->fbclid),
                filled($touch->gclid),
                filled($touch->ttclid),
            ));
        } catch (Throwable $e) {
            // Back off instead of retrying on every event while something is
            // wrong — backfill() picks the campaign up later regardless.
            Cache::put($cacheKey, true, now()->addMinutes(10));

            throw $e;
        }

        Cache::put($cacheKey, true, now()->addDay());
    }

    /**
     * Catches campaigns from traffic recorded before auto-discovery existed
     * (or any discover() missed). Run from the campaign screens, throttled
     * to once per 10 minutes.
     */
    public function backfill(): void
    {
        if (! Cache::add('marketing_campaigns_backfilled', true, now()->addMinutes(10))) {
            return;
        }

        // Runs while an admin page renders — a failure here must not take
        // the page down, it only delays discovery to the next run.
        try {
            $this->backfillFromAttributions();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function backfillFromAttributions(): void
    {
        $known = MarketingCampaign::query()
            ->whereNotNull('campaign_key')
            ->pluck('campaign_key')
            ->mapWithKeys(fn ($key) => [mb_strtolower($key) => true])
            ->all();

        // Most-used source first, so it decides the platform when one key
        // somehow arrived from several.
        MarketingAttribution::query()
            ->whereNotNull('last_touch_campaign')
            ->selectRaw('last_touch_campaign, last_touch_source')
            ->selectRaw('MAX(CASE WHEN last_touch_fbclid IS NOT NULL THEN 1 ELSE 0 END) AS has_fbclid')
            ->selectRaw('MAX(CASE WHEN last_touch_gclid IS NOT NULL THEN 1 ELSE 0 END) AS has_gclid')
            ->selectRaw('MAX(CASE WHEN last_touch_ttclid IS NOT NULL THEN 1 ELSE 0 END) AS has_ttclid')
            ->groupBy('last_touch_campaign', 'last_touch_source')
            ->orderByRaw('COUNT(*) DESC')
            ->get()
            ->each(function ($row) use (&$known) {
                $key = trim((string) $row->last_touch_campaign);

                if (! self::isUsableKey($key) || isset($known[mb_strtolower($key)])) {
                    return;
                }

                $this->ensure($key, self::platformFor(
                    $row->last_touch_source,
                    (bool) $row->has_fbclid,
                    (bool) $row->has_gclid,
                    (bool) $row->has_ttclid,
                ));

                $known[mb_strtolower($key)] = true;
            });
    }

    public function ensure(string $key, string $platform): MarketingCampaign
    {
        // firstOrCreate falls back to a re-read on a unique violation, so two
        // requests landing from the same new campaign can't create it twice
        // (campaign_key is unique).
        return MarketingCampaign::firstOrCreate(['campaign_key' => $key], fn () => [
            'marketing_source_id' => self::sourceFor($platform)->id,
            // Meta/Google campaign ids are long numbers — when the ad passes
            // {{campaign.id}} as utm_campaign, keep it as the id too.
            'external_campaign_id' => preg_match('/^\d{6,}$/', $key) ? $key : null,
            'status' => 'active',
        ]);
    }

    /** Campaigns hang off one platform-level source row (no source/medium) per platform. */
    public static function sourceFor(string $platform): MarketingSource
    {
        $platform = array_key_exists($platform, self::PLATFORMS) ? $platform : 'other';

        return MarketingSource::firstOrCreate(
            ['platform' => $platform, 'source' => null, 'medium' => null],
            ['name' => self::PLATFORMS[$platform]],
        );
    }

    public static function platformFor(?string $source, bool $fbclid = false, bool $gclid = false, bool $ttclid = false): string
    {
        $source = mb_strtolower(trim((string) $source));

        return match (true) {
            in_array($source, self::META_SOURCES, true),
            str_contains($source, 'facebook'),
            str_contains($source, 'instagram') => 'meta',
            str_contains($source, 'tiktok') => 'tiktok',
            // Checked before Google: YouTube ads run through Google Ads and
            // also carry a gclid, but an explicit youtube source wins.
            in_array($source, self::YOUTUBE_SOURCES, true),
            str_contains($source, 'youtube') => 'youtube',
            in_array($source, self::GOOGLE_SOURCES, true),
            str_contains($source, 'google') => 'google',
            $fbclid => 'meta',
            $ttclid => 'tiktok',
            $gclid => 'google',
            default => 'other',
        };
    }

    /**
     * Skips blanks and unreplaced ad macros — e.g. "{{campaign.name}}"
     * arrives literally when a link is opened from an ad preview or the
     * macro is mistyped, and isn't a real campaign. New traffic is already
     * filtered in MarketingContext; this also covers older stored rows.
     */
    public static function isUsableKey(?string $key): bool
    {
        $key = trim((string) $key);

        return $key !== '' && mb_strlen($key) <= 255 && ! MarketingContext::isUnreplacedMacro($key);
    }
}
