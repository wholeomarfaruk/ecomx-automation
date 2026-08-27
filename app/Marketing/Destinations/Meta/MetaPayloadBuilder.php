<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Events\AddToCart;
use App\Marketing\Events\InitiateCheckout;
use App\Marketing\Events\Purchase;
use App\Marketing\Events\ViewContent;

final class MetaPayloadBuilder
{
    public function build(
        EventContract $event,
        MarketingContext $context,
    ): array {
        return [
            'data' => [
                array_filter([
                    'event_name' => $event->eventName(),
                    'event_time' => $event->occurredAt()->timestamp,
                    'event_id' => $event->eventId(),
                    'event_source_url' => $context->pageUrl,
                    'action_source' => 'website',
                    'user_data' => $this->buildUserData($context),
                    'custom_data' => $this->buildCustomData($event),
                ], fn ($value) => $value !== null),
            ],
        ];
    }

    private function buildUserData(
        MarketingContext $context,
    ): array {
        return array_filter([
            'client_ip_address' => $context->ipAddress,
            'client_user_agent' => $context->userAgent,
            'external_id' => $this->resolveExternalId($context),
            'fbp' => $context->trackingCookies['_fbp'] ?? null,
            'fbc' => $context->trackingCookies['_fbc'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function buildCustomData(
        EventContract $event,
    ): array {
        return match (true) {
            $event instanceof Purchase => $this->buildPurchaseData($event),
            $event instanceof ViewContent, $event instanceof AddToCart => $this->buildContentData($event),
            $event instanceof InitiateCheckout => $this->buildCheckoutData($event),
            default => $this->buildGenericData($event),
        };
    }

    private function buildPurchaseData(
        Purchase $event,
    ): array {
        return array_filter([
            'value' => $event->value,
            'currency' => $event->currency,
            'order_id' => $event->orderId,

            'contents' => array_map(
                fn (array $item) => array_filter([
                    'id' => $item['item_id'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'item_price' => $item['price'] ?? null,
                ], fn ($value) => $value !== null),
                $event->items
            ),

            'content_ids' => array_values(array_filter(
                array_column($event->items, 'item_id')
            )),

            'content_type' => 'product',
        ]);
    }

    private function buildContentData(
        ViewContent|AddToCart $event,
    ): array {
        return array_filter([
            'value' => $event->value,
            'currency' => $event->currency,
            'content_ids' => $event->contentId ? [(string) $event->contentId] : null,
            'content_name' => $event->contentName,
            'content_type' => $event->contentType ?? 'product',
        ]);
    }

    private function buildCheckoutData(
        InitiateCheckout $event,
    ): array {
        return array_filter([
            'value' => $event->value,
            'currency' => $event->currency,
            'contents' => $event->items,
            'content_ids' => array_values(array_filter(
                array_column($event->items, 'item_id')
            )),
            'content_type' => 'product',
            'num_items' => $event->itemCount ?? count($event->items),
        ]);
    }

    private function buildGenericData(
        EventContract $event,
    ): array {
        $data = method_exists($event, 'data') ? $event->data() : [];
        $parameters = method_exists($event, 'parameters') ? $event->parameters() : [];

        return array_filter(
            array_merge($data, $parameters),
            fn ($value) => $value !== null,
        );
    }

    private function resolveExternalId(
        MarketingContext $context,
    ): ?string {
        return match (true) {
            $context->customer !== null => (string) $context->customer->id,
            $context->user !== null => (string) $context->user->id,
            default => null,
        };
    }
}
