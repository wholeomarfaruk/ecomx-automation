<?php

namespace App\Marketing\Browser;

use App\Marketing\Attribution\AttributionTouch;
use App\Marketing\Attribution\MarketingAttribution;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Data\MarketingEventData;
use App\Marketing\Events\AddToCart;
use App\Marketing\Events\ViewContent;

/**
 * Builds the universal dataLayer payload for a canonical marketing event.
 * Destination-agnostic by design — no Meta/GA4/TikTok-specific field names
 * or transforms here. GTM tags do that mapping on the browser side, from
 * this same payload.
 */
final class BrowserEventPayloadBuilder
{
    public function build(MarketingEventData $data): array
    {
        $event = $data->event;
        $eventData = method_exists($event, 'data') ? $event->data() : [];

        return array_filter([
            'event' => $this->gtmEventName($event),

            'marketing' => [
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'occurred_at' => $event->occurredAt()->toISOString(),
                'source' => 'website',
                'channel' => 'browser',
            ],

            'ecommerce' => $this->buildEcommerce($event, $eventData),

            'page' => array_filter([
                'url' => $data->context->pageUrl,
                'path' => $data->context->pageUrl ? (parse_url($data->context->pageUrl, PHP_URL_PATH) ?: null) : null,
            ], fn ($value) => $value !== null) ?: null,

            'attribution' => $this->buildAttribution($data->attribution),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function gtmEventName(EventContract $event): string
    {
        return match ($event->eventName()) {
            'PageView' => 'page_view',
            'ViewContent' => 'view_content',
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
            $item = array_filter([
                'item_id' => $event->contentId !== null ? (string) $event->contentId : null,
                'item_name' => $event->contentName,
                'price' => $data['value'] ?? null,
                'quantity' => $event instanceof AddToCart ? $event->quantity : null,
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
