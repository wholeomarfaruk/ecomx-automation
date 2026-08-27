<?php

namespace App\Marketing\Jobs;

use App\Marketing\Attribution\AttributionTouch;
use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Context\MarketingContext;
use App\Marketing\Destinations\DestinationRegistry;
use App\Marketing\Events\MarketingEventFactory;
use App\Marketing\Contracts\EventContract;
use App\Models\Customer;
use App\Models\Marketing\MarketingEvent as MarketingEventModel;
use App\Models\Marketing\MarketingEventDestination;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class DispatchMarketingEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly array $event,
        public readonly array $context,
        public readonly array $destinations = [],
        public readonly string $channel = 'server',
    ) {}

    public function handle(
        MarketingEventFactory $eventFactory,
        DestinationRegistry $registry,
    ): void {
        $event = $eventFactory->make($this->event);
        $context = $this->rebuildContext();
        $eventModel = $this->findEventModel($event);

        foreach ($this->destinations as $destinationKey) {
            $this->deliverToDestination($registry, $destinationKey, $event, $context, $eventModel);
        }
    }

    private function deliverToDestination(
        DestinationRegistry $registry,
        string $destinationKey,
        EventContract $event,
        MarketingContext $context,
        ?MarketingEventModel $eventModel,
    ): void {
        $record = $this->resolveDeliveryRecord($eventModel, $destinationKey);

        // Idempotency: a prior attempt already succeeded (e.g. this job
        // retried after a partial failure on a different destination) —
        // never re-send to a destination that already confirmed delivery.
        if ($record && $record->status?->value === 'success') {
            return;
        }

        $destination = $registry->get($destinationKey);

        $startedAt = microtime(true);
        $result = $destination->send(event: $event, context: $context);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if (! $record) {
            return; // no marketing_events row to attach delivery history to
        }

        $record->forceFill([
            'status' => $result->status,
            'attempts' => $record->attempts + 1,
            'first_attempted_at' => $record->first_attempted_at ?? now(),
            'last_attempted_at' => now(),
            'delivered_at' => $result->success ? now() : $record->delivered_at,
            'external_event_id' => $result->externalEventId ?? $record->external_event_id,
            'http_status' => $result->httpStatus,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'duration_ms' => $durationMs,
        ])->save();

        // A retryable failure throws so Laravel's own job retry
        // (tries/backoff) picks this destination back up on the next
        // attempt — the status/attempts row above is already saved, so a
        // retry resumes cleanly. A non-retryable failure is left as a
        // permanent 'failed' row instead of throwing, since retrying a
        // request that will fail identically every time just burns the
        // job's attempt budget for no benefit.
        if (! $result->success && $result->retryable) {
            throw new \RuntimeException(
                "Marketing destination [{$destinationKey}] failed retryably: {$result->errorMessage}"
            );
        }
    }

    private function resolveDeliveryRecord(
        ?MarketingEventModel $eventModel,
        string $destinationKey,
    ): ?MarketingEventDestination {
        if (! $eventModel) {
            return null;
        }

        return MarketingEventDestination::query()->firstOrCreate(
            [
                'marketing_event_id' => $eventModel->id,
                'destination' => $destinationKey,
                'channel' => $this->channel,
            ],
            ['status' => 'pending'],
        );
    }

    private function findEventModel(EventContract $event): ?MarketingEventModel
    {
        return MarketingEventModel::query()
            ->where('event_id', $event->eventId())
            ->first();
    }

    private function rebuildContext(): MarketingContext
    {
        return new MarketingContext(
            ipAddress: $this->context['ip_address'] ?? null,
            userAgent: $this->context['user_agent'] ?? null,
            acceptLanguage: $this->context['accept_language'] ?? null,

            host: $this->context['host'] ?? null,
            pageUrl: $this->context['page_url'] ?? null,
            referrer: $this->context['referrer'] ?? null,

            deviceFingerprint: $this->context['device_fingerprint'] ?? null,
            sessionId: $this->context['session_id'] ?? null,

            trackingCookies: $this->context['tracking_cookies'] ?? [],

            customer: $this->findCustomer(),
            user: $this->findUser(),

            attribution: $this->rebuildAttribution(),
        );
    }

    private function rebuildAttribution(): ?MarketingAttribution
    {
        $attribution = $this->context['attribution'] ?? null;

        if (! $attribution) {
            return null;
        }

        return new MarketingAttribution(
            firstTouch: AttributionTouch::fromStored($attribution['first_touch'] ?? null),
            lastTouch: AttributionTouch::fromStored($attribution['last_touch'] ?? null),
            currentTouch: AttributionTouch::fromStored($attribution['current_touch'] ?? null),
        );
    }

    private function findCustomer(): ?Customer
    {
        $customerId = $this->context['customer_id'] ?? null;

        if (! $customerId) {
            return null;
        }

        return Customer::find($customerId);
    }

    private function findUser(): ?User
    {
        $userId = $this->context['user_id'] ?? null;

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }
}
