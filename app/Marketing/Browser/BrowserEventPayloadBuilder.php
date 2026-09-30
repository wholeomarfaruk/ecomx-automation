<?php

namespace App\Marketing\Browser;

use App\Marketing\Attribution\AttributionTouch;
use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Data\MarketingEventData;
use App\Marketing\Destinations\Meta\MetaBrowserData;
use App\Marketing\Events\AddToCart;
use App\Marketing\Events\InitiateCheckout;
use App\Marketing\Events\Purchase;
use App\Marketing\Events\ViewContent;

/**
 * Builds the universal dataLayer payload for a canonical marketing event.
 * Destination-agnostic (GA4-style ecommerce) — GTM tags map it on the
 * browser side. The one exception is the `meta` block: Meta's hashed
 * advanced matching and custom_data can't be derived in GTM, so it's built
 * server-side by the same code as the Conversions API payload (see
 * MetaBrowserData).
 */
final class BrowserEventPayloadBuilder
{
    public function __construct(
        private readonly MetaBrowserData $metaBrowserData,
    ) {}

    public function build(MarketingEventData $data): array
    {
        $event = $data->event;
        $eventData = method_exists($event, 'data') ? $event->data() : [];

        return array_filter([
            'event' => $this->gtmEventName($event),

            'marketing' => array_filter([
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'occurred_at' => $event->occurredAt()->toISOString(),
                'source' => 'website',
                'channel' => 'browser',
                // For a GTM Meta Pixel tag: whether ecommerce.items[].item_id
                // are catalog items or item groups (see CatalogItemId).
                'content_type' => $this->contentType($event),
            ], fn ($value) => $value !== null),

            'ecommerce' => $this->buildEcommerce($event, $eventData),

            'page' => array_filter([
                'url' => $data->context->pageUrl,
                'path' => $data->context->pageUrl ? (parse_url($data->context->pageUrl, PHP_URL_PATH) ?: null) : null,
            ], fn ($value) => $value !== null) ?: null,

            'attribution' => $this->buildAttribution($data->attribution),

            'meta' => $this->metaBrowserData->for($data),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function contentType(EventContract $event): ?string
    {
        return match (true) {
            $event instanceof ViewContent => $event->contentType ?? 'product',
            $event instanceof AddToCart, $event instanceof InitiateCheckout, $event instanceof Purchase => 'product',
            default => null,
        };
    }

    /**
     * GA4's recommended ecommerce event names — the names GTM's GA4 tags
     * and Meta Pixel tag templates (event name inherited from the
     * dataLayer) recognise and map to ViewContent/AddToCart/….
     */
    private function gtmEventName(EventContract $event): string
    {
        return match ($event->eventName()) {
            'PageView' => 'page_view',
            'ViewContent' => 'view_item',
            'AddToCart' => 'add_to_cart',
            'InitiateCheckout' => 'begin_checkout',
            'Purchase' => 'purchase',
            'Lead' => 'generate_lead',
            default => strtolower($event->eventName()),
        };
    }

    private function buildEcommerce(EventContract $event, array $data): ?array
    {
        if ($data === []) {
            return null;
        }

        $ecommerce = array_filter([
            'transaction_id' => $data['order_id'] ?? null,
            'value' => $data['value'] ?? null,
            'currency' => $data['currency'] ?? null,
        ], fn ($value) => $value !== null);

        $items = $this->buildItems($event, $data);

        if ($items !== []) {
            $ecommerce['items'] = $items;
        }

        return $ecommerce ?: null;
    }

    private function buildItems(EventContract $event, array $data): array
    {
        if (isset($data['items']) && is_array($data['items'])) {
            return array_map(
                fn (array $item) => array_filter([
                    'item_id' => $item['item_id'] ?? (isset($item['product_id']) ? (string) $item['product_id'] : null),
                    'item_name' => $item['item_name'] ?? $item['product_name'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'price' => $item['price'] ?? $item['unit_price'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                ], fn ($value) => $value !== null),
                $data['items'],
            );
        }

        if ($event instanceof AddToCart || $event instanceof ViewContent) {
            $value = $data['value'] ?? null;
            $quantity = $event instanceof AddToCart ? $event->quantity : null;

            // GA4 reads price as the UNIT price (revenue = price × quantity),
            // while an AddToCart's value is already the whole add.
            $price = $value !== null && $quantity > 0 ? round($value / $quantity, 2) : $value;

            $item = array_filter([
                'item_id' => $event->contentId !== null ? (string) $event->contentId : null,
                'item_name' => $event->contentName,
                'price' => $price,
                'quantity' => $quantity,
            ], fn ($value) => $value !== null);

            return $item !== [] ? [$item] : [];
        }

        return [];
    }

    private function buildAttribution(MarketingAttribution $attribution): ?array
    {
        $touch = $attribution->lastTouch ?? $attribution->firstTouch;

        if (! $touch instanceof AttributionTouch) {
            return null;
        }

        return array_filter([
            'source' => $touch->source,
            'medium' => $touch->medium,
            'campaign' => $touch->campaign,
            'term' => $touch->term,
            'content' => $touch->content,
        ], fn ($value) => $value !== null) ?: null;
    }
}
