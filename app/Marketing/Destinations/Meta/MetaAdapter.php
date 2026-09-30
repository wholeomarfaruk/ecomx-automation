<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Contracts\MarketingDestinationContract;
use App\Marketing\DTOs\DestinationResult;
use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class MetaAdapter implements MarketingDestinationContract
{
    /** 4xx client errors mean the request itself is wrong — retrying an
     *  identical payload will fail identically, so only 429 (rate limit) is
     *  worth retrying among them. */
    private const RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        private readonly MetaPayloadBuilder $payloadBuilder,
    ) {}

    public function key(): string
    {
        return 'meta';
    }

    public function send(
        EventContract $event,
        MarketingContext $context,
    ): DestinationResult {
        $pixelId = $this->credential('pixel_id');
        $accessToken = $this->credential('access_token');

        if (blank($pixelId) || blank($accessToken)) {
            return DestinationResult::failed(
                errorCode: 'not_configured',
                errorMessage: 'Meta Pixel ID or Access Token is not set (Site Settings → Marketing).',
            );
        }

        $payload = $this->payloadBuilder->build(
            event: $event,
            context: $context,
        );

        if ($testEventCode = $this->credential('test_event_code')) {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $response = Http::withToken($accessToken)->post(
                $this->endpoint($pixelId),
                $payload,
            );
        } catch (ConnectionException $e) {
            return DestinationResult::failed(
                errorCode: 'connection_error',
                errorMessage: $e->getMessage(),
                retryable: true,
            );
        }

        if ($response->successful()) {
            // Only while testing — logging every live event would flood the
            // log, but with a test code set this confirms Meta accepted it.
            if ($testEventCode) {
                Log::channel('marketing')->info('Meta CAPI test event accepted', [
                    'event' => $event->eventName(),
                    'event_id' => $event->eventId(),
                    'test_event_code' => $testEventCode,
                    'response' => $response->json(),
                ]);
            }

            return DestinationResult::success(
                externalEventId: $event->eventId(),
                httpStatus: $response->status(),
            );
        }

        $body = $response->json();

        return DestinationResult::failed(
            httpStatus: $response->status(),
            errorCode: $body['error']['type'] ?? (string) $response->status(),
            errorMessage: $body['error']['message'] ?? $response->body(),
            retryable: in_array($response->status(), self::RETRYABLE_STATUSES, true),
        );
    }

    /**
     * Read at send time, not from boot-time config: a long-running queue
     * worker boots once, so config set in AppServiceProvider goes stale the
     * moment an admin changes Site Settings → Marketing. The Setting cache is
     * shared across processes and cleared on save, so this is always fresh.
     * Falls back to the .env-driven config like AppServiceProvider does.
     */
    private function credential(string $key): ?string
    {
        $value = Setting::get("meta_{$key}", null, 'marketing');

        return filled($value) ? (string) $value : config("services.meta.{$key}");
    }

    private function endpoint(string $pixelId): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/events',
            $this->credential('api_version') ?: 'v23.0',
            $pixelId,
        );
    }
}
