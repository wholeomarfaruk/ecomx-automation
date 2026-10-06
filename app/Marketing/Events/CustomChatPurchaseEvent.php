<?php

namespace App\Marketing\Events;

use Carbon\CarbonInterface;

/**
 * A Purchase for an order taken over chat (Messenger/WhatsApp) and placed
 * in admin — sent to Meta CAPI as event_name "Purchase" with action_source
 * "chat". Kept apart from the storefront's Purchase so the website event's
 * payload never changes because of it.
 */
final class CustomChatPurchaseEvent extends MarketingEvent
{
    /** Serialized as this, so the queued job rebuilds this class, not Purchase. */
    public const KIND = 'CustomChatPurchase';

    public function __construct(
        string $eventId,
        CarbonInterface $occurredAt,

        public readonly float $value,
        public readonly string $currency,

        public readonly string|int|null $orderId = null,

        public readonly array $items = [],

        public readonly ?float $shipping = null,

        array $parameters = [],
    ) {
        parent::__construct(
            eventId: $eventId,
            occurredAt: $occurredAt,
            parameters: $parameters,
        );
    }

    public static function create(
        float $value,
        string $currency,
        string|int|null $orderId = null,
        array $items = [],
        ?float $shipping = null,
        array $parameters = [],
        ?string $eventId = null,
    ): self {
        return new self(
            eventId: $eventId ?? self::generateEventId(),
            occurredAt: self::now(),
            value: $value,
            currency: $currency,
            orderId: $orderId,
            items: $items,
            shipping: $shipping,
            parameters: $parameters,
        );
    }

    public function eventName(): string
    {
        return 'Purchase';
    }

    public function actionSource(): string
    {
        return 'chat';
    }

    public function data(): array
    {
        return [
            'value' => $this->value,
            'currency' => $this->currency,
            'order_id' => $this->orderId,
            'items' => $this->items,
            'shipping' => $this->shipping,
        ];
    }
}
