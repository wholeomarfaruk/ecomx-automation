<?php

namespace App\Marketing\Services;

use App\Marketing\Attribution\AttributionService;
use App\Marketing\Browser\BrowserEventPayloadBuilder;
use App\Marketing\Context\MarketingContext;
use App\Marketing\Context\MarketingContextBuilder;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Data\MarketingEventData;
use App\Marketing\Events\Purchase;
use App\Marketing\Identity\IdentityResolver;
use App\Marketing\Jobs\DispatchMarketingEventJob;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Marketing\MarketingEvent as MarketingEventModel;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class MarketingEventService
{
    public function __construct(
        private readonly IdentityResolver $identityResolver,
        private readonly AttributionService $attributionService,
        private readonly BrowserEventPayloadBuilder $browserPayloadBuilder,
        private readonly MarketingContextBuilder $contextBuilder,
        private readonly MarketingSessionResolver $sessionResolver,
    ) {}

    /**
     * Convenience wrapper for the common mid-request case (Livewire
     * component actions: ViewContent, AddToCart, InitiateCheckout): builds
     * context once, resolves/reuses the active marketing_session for this
     * device, persists the event, and builds the browser/GTM payload for
     * it — all in one call and one context build, so call sites don't each
     * have to repeat that boilerplate.
     *
     * Requires an active HTTP request with a device already resolved by
     * DeviceTracker (i.e. $request->attributes->get('device')) — not for
     * use from queued jobs, webhooks, or console commands.
     *
     * @return array{model: MarketingEventModel, browserPayload: array}
     */
    public function recordForCurrentRequest(
        EventContract $event,
        Device $device,
        ?Customer $customer = null,
    ): array {
        $context = $this->contextBuilder->build(
            deviceFingerprint: $device->fingerprint,
            customer: $customer,
        );

        $attribution = $this->attributionService->resolve($context);
        $context = $context->withAttribution($attribution);

        $session = $this->sessionResolver->resolve($device, $customer, $context);

        $model = $this->record(
            event: $event,
            context: $context,
            deviceId: $device->id,
            customerId: $customer?->id,
            sessionId: $session->id,
        );

        $this->dispatchDestinations($event, $context);

        $browserPayload = $this->browserPayloadBuilder->build(
            $this->prepare($event, $context)
        );

        return ['model' => $model, 'browserPayload' => $browserPayload];
    }

    /**
     * Queues server-side destination delivery (Meta CAPI, etc.) for an
     * already-persisted event. Split out from record() because record() is
     * also called directly in places that don't want delivery queued yet
     * (none currently, but keeps the persistence and delivery concerns
     * separable) — every real call site above calls both.
     *
     * Queued after the current DB transaction commits: DispatchMarketingEventJob
     * looks the event up by event_id, so a worker must never be able to pick
     * this job up before the marketing_events row it depends on is actually
     * committed.
     */
    private function dispatchDestinations(
        EventContract $event,
        MarketingContext $context,
    ): void {
        $destinations = config('marketing.destinations', []);

        if ($destinations === []) {
            return;
        }

        DispatchMarketingEventJob::dispatch(
            event: $this->serializeEvent($event),
            context: $this->serializeContext($context),
            destinations: $destinations,
            channel: 'server',
        )->afterCommit();
    }

    /**
     * Persists the canonical event ledger row (+ items + attribution
     * snapshot) for the given event/context. Does not dispatch to any
     * destination — that remains dispatch()'s job.
     *
     * Purchase events are idempotent on order_id: reloading/retrying the
     * same order reuses the existing marketing_events row and its event_id
     * instead of creating a duplicate.
     */
    public function record(
        EventContract $event,
        MarketingContext $context,
        ?int $deviceId,
        ?int $customerId,
        ?int $sessionId,
    ): MarketingEventModel {
        $attribution = $context->attribution ?? $this->attributionService->resolve($context);

        $orderId = $event instanceof Purchase ? $this->resolveOrderId($event->orderId) : null;

        if ($orderId) {
            $existing = MarketingEventModel::query()
                ->where('order_id', $orderId)
                ->where('event_name', $event->eventName())
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($event, $context, $attribution, $deviceId, $customerId, $sessionId, $orderId) {
            $data = method_exists($event, 'data') ? $event->data() : [];

            $model = MarketingEventModel::create([
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'occurred_at' => $event->occurredAt(),
                'received_at' => now(),

                'device_id' => $deviceId,
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'order_id' => $orderId,

                'page_url' => $context->pageUrl,
                'page_path' => $context->pageUrl ? (parse_url($context->pageUrl, PHP_URL_PATH) ?: null) : null,

                'referrer_url' => $context->referrer,
                'referrer_domain' => $context->referrer ? parse_url($context->referrer, PHP_URL_HOST) : null,

                'ip_address' => $context->ipAddress,
                'user_agent' => $context->userAgent,

                'currency' => $data['currency'] ?? null,
                'value' => $data['value'] ?? null,

                'content_type' => $data['content_type'] ?? null,
                'content_id' => $data['content_id'] ?? null,
                'content_name' => $data['content_name'] ?? null,

                'source' => $attribution->currentTouch?->source,
                'medium' => $attribution->currentTouch?->medium,
                'campaign' => $attribution->currentTouch?->campaign,
                'term' => $attribution->currentTouch?->term,
                'content' => $attribution->currentTouch?->content,

                'utm_source' => $context->trackingParameters['utm_source'] ?? null,
                'utm_medium' => $context->trackingParameters['utm_medium'] ?? null,
                'utm_campaign' => $context->trackingParameters['utm_campaign'] ?? null,
                'utm_term' => $context->trackingParameters['utm_term'] ?? null,
                'utm_content' => $context->trackingParameters['utm_content'] ?? null,

                'fbclid' => $context->trackingParameters['fbclid'] ?? null,
                'gclid' => $context->trackingParameters['gclid'] ?? null,
                'ttclid' => $context->trackingParameters['ttclid'] ?? null,

                'event_source' => 'website',
                'event_channel' => 'server',

                'commerce_data' => $data ?: null,
                'custom_data' => method_exists($event, 'parameters') ? ($event->parameters() ?: null) : null,
            ]);

            $this->recordItems($model, $data['items'] ?? []);

            $model->attribution()->create([
                'first_touch_source' => $attribution->firstTouch?->source,
                'first_touch_medium' => $attribution->firstTouch?->medium,
                'first_touch_campaign' => $attribution->firstTouch?->campaign,
                'first_touch_term' => $attribution->firstTouch?->term,
                'first_touch_content' => $attribution->firstTouch?->content,
                'first_touch_landing_url' => $attribution->firstTouch?->landingUrl,
                'first_touch_landing_path' => $this->pathFromUrl($attribution->firstTouch?->landingUrl),
                'first_touch_at' => $attribution->firstTouch?->capturedAt,
                'first_touch_fbclid' => $attribution->firstTouch?->fbclid,
                'first_touch_gclid' => $attribution->firstTouch?->gclid,
                'first_touch_ttclid' => $attribution->firstTouch?->ttclid,

                'last_touch_source' => $attribution->lastTouch?->source,
                'last_touch_medium' => $attribution->lastTouch?->medium,
                'last_touch_campaign' => $attribution->lastTouch?->campaign,
                'last_touch_term' => $attribution->lastTouch?->term,
                'last_touch_content' => $attribution->lastTouch?->content,
                'last_touch_url' => $attribution->lastTouch?->landingUrl,
                'last_touch_landing_path' => $this->pathFromUrl($attribution->lastTouch?->landingUrl),
                'last_touch_at' => $attribution->lastTouch?->capturedAt,
                'last_touch_fbclid' => $attribution->lastTouch?->fbclid,
                'last_touch_gclid' => $attribution->lastTouch?->gclid,
                'last_touch_ttclid' => $attribution->lastTouch?->ttclid,

                'session_source' => $attribution->currentTouch?->source,
                'session_medium' => $attribution->currentTouch?->medium,
                'session_campaign' => $attribution->currentTouch?->campaign,
                'session_term' => $attribution->currentTouch?->term,
                'session_content' => $attribution->currentTouch?->content,
                'session_fbclid' => $attribution->currentTouch?->fbclid,
                'session_gclid' => $attribution->currentTouch?->gclid,
                'session_ttclid' => $attribution->currentTouch?->ttclid,
            ]);

            return $model;
        });
    }

    private function pathFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return parse_url($url, PHP_URL_PATH) ?: '/';
    }

    private function recordItems(MarketingEventModel $model, array $items): void
    {
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;
            $unitPrice = $item['price'] ?? $item['unit_price'] ?? null;

            $model->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $item['item_name'] ?? $item['product_name'] ?? null,
                'sku' => $item['sku'] ?? null,
                'item_id' => $item['item_id'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_value' => $unitPrice !== null ? $unitPrice * $quantity : null,
                'currency' => $item['currency'] ?? null,
            ]);
        }
    }

    /**
     * Records a Purchase event for a confirmed order. Not called from
     * anywhere yet — the storefront checkout flow (Checkout::placeOrder())
     * is currently a demo that never creates a real Order record, so there
     * is no genuine "order confirmed" trigger point to call this from. It
     * exists so that whoever builds real order creation only has to add one
     * call, instead of re-deriving this mapping.
     *
     * Idempotent via record()'s order_id lookup: calling this twice for the
     * same order (e.g. a webhook retry, or a user reloading a success page)
     * reuses the existing marketing_events row instead of duplicating it.
     *
     * Order/OrderItem are read-only here — nothing on those models is
     * modified. Item fields are mapped explicitly because OrderItem's
     * column names (product_id/quantity/unit_price) don't match the
     * Purchase event's expected item shape (item_id/quantity/price) used by
     * MetaPayloadBuilder and BrowserPayloadBuilder.
     */
    public function recordPurchase(
        Order $order,
        MarketingContext $context,
        ?int $deviceId,
        ?int $sessionId,
    ): MarketingEventModel {
        $order->loadMissing('items');

        $items = $order->items->map(fn ($item) => [
            'item_id' => $item->sku ?? (string) $item->product_id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'item_name' => $item->product_name,
            'sku' => $item->sku,
            'quantity' => (float) $item->quantity,
            'price' => (float) $item->unit_price,
            'currency' => $order->currency,
        ])->all();

        $event = Purchase::create(
            value: (float) $order->total_amount,
            currency: $order->currency,
            orderId: $order->id,
            items: $items,
        );

        $model = $this->record(
            event: $event,
            context: $context,
            deviceId: $deviceId,
            customerId: $order->customer_id,
            sessionId: $sessionId,
        );

        $this->dispatchDestinations($event, $context);

        return $model;
    }

    /**
     * Purchase::orderId is string|int|null on the canonical event (it may be
     * a human-readable order number in future call sites) — the DB column
     * is a numeric FK to orders.id, so only a genuinely numeric value maps.
     */
    private function resolveOrderId(string|int|null $orderId): ?int
    {
        if ($orderId === null || $orderId === '') {
            return null;
        }

        return is_numeric($orderId) ? (int) $orderId : null;
    }

    public function prepare(
        EventContract $event,
        MarketingContext $context,
    ): MarketingEventData {
        $attribution = $this->attributionService->resolve($context);
        $contextWithAttribution = $context->withAttribution($attribution);

        return new MarketingEventData(
            event: $event,
            context: $contextWithAttribution,
            identity: $this->identityResolver->resolve($contextWithAttribution),
            attribution: $attribution,
        );
    }

    private function serializeEvent(
        EventContract $event,
    ): array {
        return [
            'name' => $event->eventName(),
            'event_id' => $event->eventId(),
            'occurred_at' => $event->occurredAt()->toISOString(),

            'data' => method_exists($event, 'data')
                ? $event->data()
                : [],

            'parameters' => method_exists($event, 'parameters')
                ? $event->parameters()
                : [],
        ];
    }

    private function serializeContext(
        MarketingContext $context,
    ): array {
        return [
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'accept_language' => $context->acceptLanguage,

            'host' => $context->host,
            'page_url' => $context->pageUrl,
            'referrer' => $context->referrer,

            'device_fingerprint' => $context->deviceFingerprint,
            'session_id' => $context->sessionId,

            'tracking_cookies' => $context->trackingCookies,

            'customer_id' => $context->customer?->id,
            'user_id' => $context->user?->id,

            'attribution' => $context->attribution?->toArray(),
        ];
    }
}
