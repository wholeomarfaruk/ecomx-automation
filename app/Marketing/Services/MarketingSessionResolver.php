<?php

namespace App\Marketing\Services;

use App\Marketing\Context\MarketingContext;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Marketing\MarketingSession;
use Illuminate\Support\Str;

/**
 * Shared by MarketingTracker (PageView on every request) and any code that
 * records a business event mid-request (AddToCart, InitiateCheckout, ...) —
 * both need the same "reuse the active session or start a new one" logic,
 * so it lives here once instead of being duplicated at each call site.
 */
final class MarketingSessionResolver
{
    public function resolve(
        Device $device,
        ?Customer $customer,
        MarketingContext $context,
    ): MarketingSession {
        $timeoutMinutes = (int) config('marketing.session_timeout_minutes', 30);

        $active = MarketingSession::query()
            ->where('device_id', $device->id)
            ->where('last_activity_at', '>=', now()->subMinutes($timeoutMinutes))
            ->orderByDesc('last_activity_at')
            ->first();

        if ($active) {
            $active->forceFill([
                'last_activity_at' => now(),
                'customer_id' => $customer?->id ?? $active->customer_id,
                'exit_url' => $context->pageUrl,
                'exit_path' => parse_url((string) $context->pageUrl, PHP_URL_PATH) ?: null,
            ])->save();

            return $active;
        }

        $tracking = $context->trackingParameters;

        return MarketingSession::create([
            'uuid' => (string) Str::uuid(),
            'device_id' => $device->id,
            'customer_id' => $customer?->id,
            'started_at' => now(),
            'last_activity_at' => now(),
            'landing_url' => $context->pageUrl,
            'landing_path' => parse_url((string) $context->pageUrl, PHP_URL_PATH) ?: null,
            'referrer_url' => $context->referrer,
            'referrer_domain' => $context->referrer ? parse_url($context->referrer, PHP_URL_HOST) : null,
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'device_type' => $device->device_type,
            'platform' => $device->platform,
            'browser' => $device->browser,
            'operating_system' => $device->operating_system,
            'language' => $device->language,
            'utm_source' => $tracking['utm_source'] ?? null,
            'utm_medium' => $tracking['utm_medium'] ?? null,
            'utm_campaign' => $tracking['utm_campaign'] ?? null,
            'utm_term' => $tracking['utm_term'] ?? null,
            'utm_content' => $tracking['utm_content'] ?? null,
            'fbclid' => $tracking['fbclid'] ?? null,
            'gclid' => $tracking['gclid'] ?? null,
            'ttclid' => $tracking['ttclid'] ?? null,
        ]);
    }
}
