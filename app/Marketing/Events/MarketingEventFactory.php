<?php

namespace App\Marketing\Events;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class MarketingEventFactory
{
    public function make(
        array $payload,
    ): MarketingEvent {
        return match ($payload['name'] ?? null) {
            'PageView' => $this->makePageView($payload),
            'ViewContent' => $this->makeViewContent($payload),
            'AddToCart' => $this->makeAddToCart($payload),
            'InitiateCheckout' => $this->makeInitiateCheckout($payload),
            'Purchase' => $this->makePurchase($payload),
            'Lead' => $this->makeLead($payload),
            default => throw new InvalidArgumentException(
                'Unknown marketing event.'
            ),
        };
    }

    private function occurredAt(
        array $payload,
    ): CarbonImmutable {
        return CarbonImmutable::parse(
            $payload['occurred_at']
        );
    }

    private function makePageView(
        array $payload,
    ): PageView {
        return new PageView(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),
            parameters: $payload['parameters'] ?? [],
        );
    }

    private function makeViewContent(
        array $payload,
    ): ViewContent {
        $data = $payload['data'] ?? [];

        return new ViewContent(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),

            contentId: $data['content_id'] ?? null,
            contentName: $data['content_name'] ?? null,
            contentType: $data['content_type'] ?? null,
            value: $data['value'] ?? null,
            currency: $data['currency'] ?? null,

            parameters: $payload['parameters'] ?? [],
        );
    }

    private function makeAddToCart(
        array $payload,
    ): AddToCart {
        $data = $payload['data'] ?? [];

        return new AddToCart(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),

            contentId: $data['content_id'] ?? null,
            contentName: $data['content_name'] ?? null,
            quantity: $data['quantity'] ?? 1,
            value: $data['value'] ?? null,
            currency: $data['currency'] ?? null,

            parameters: $payload['parameters'] ?? [],
        );
    }

    private function makeInitiateCheckout(
        array $payload,
    ): InitiateCheckout {
        $data = $payload['data'] ?? [];

        return new InitiateCheckout(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),

            value: $data['value'] ?? null,
            currency: $data['currency'] ?? null,
            items: $data['items'] ?? [],
            itemCount: $data['item_count'] ?? null,

            parameters: $payload['parameters'] ?? [],
        );
    }

    private function makePurchase(
        array $payload,
    ): Purchase {
        $data = $payload['data'] ?? [];

        return new Purchase(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),

            value: (float) ($data['value'] ?? 0),
            currency: (string) ($data['currency'] ?? 'BDT'),
            orderId: $data['order_id'] ?? null,
            items: $data['items'] ?? [],

            parameters: $payload['parameters'] ?? [],
        );
    }

    private function makeLead(
        array $payload,
    ): Lead {
        $data = $payload['data'] ?? [];

        return new Lead(
            eventId: $payload['event_id'],
            occurredAt: $this->occurredAt($payload),

            leadId: $data['lead_id'] ?? null,
            value: $data['value'] ?? null,
            currency: $data['currency'] ?? null,

            parameters: $payload['parameters'] ?? [],
        );
    }
}
