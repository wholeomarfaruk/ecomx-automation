<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Contracts\MarketingDestinationContract;
use App\Marketing\DTOs\DestinationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

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
        $payload = $this->payloadBuilder->build(
            event: $event,
            context: $context,
        );

        if ($testEventCode = config('services.meta.test_event_code')) {
            $payload['test_event_code'] = $testEventCode;
        }

        try {
            $response = Http::withToken(
                config('services.meta.access_token')
            )->post(
                $this->endpoint(),
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

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/events',
            config('services.meta.api_version'),
            config('services.meta.pixel_id'),
        );
    }
}
