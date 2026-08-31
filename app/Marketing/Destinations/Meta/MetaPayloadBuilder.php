<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Events\AddToCart;
use App\Marketing\Events\InitiateCheckout;
use App\Marketing\Events\Purchase;
use App\Marketing\Events\ViewContent;
use App\Support\PhoneNumber;

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
            'em' => $this->hashedEmail($context),
            'ph' => $this->hashedPhone($context),
        ], fn ($value) => $value !== null);
    }

    /**
     * Meta's Advanced Matching wants email lowercased/trimmed, then
     * SHA-256 hashed (hex), per
     * https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
     *
     * Phone-only registrations and guest checkouts get an auto-generated
     * placeholder address (user+xxxx@<app-host>, guest+<phone>@<host>) since
     * this storefront doesn't require a real email — those aren't the
     * customer's actual email and must never be sent to Meta as if they were.
     */
    private function hashedEmail(MarketingContext $context): ?string
    {
        $email = $context->customer->email ?? $context->user->email ?? null;

        if (! $email || str_starts_with($email, 'user+') || str_starts_with($email, 'guest+')) {
            return null;
        }

        return hash('sha256', strtolower(trim($email)));
    }

    /**
     * Meta requires phone as digits only, country code first, no leading 0
     * and no symbols (e.g. 8801761234567), then SHA-256 hashed (hex) — see
     * the customer-information-parameters doc linked above. PhoneNumber
     * already resolves country_code + national number from whatever format
     * was stored, so this just concatenates and strips the "+".
     */
    private function hashedPhone(MarketingContext $context): ?string
    {
        $phone = $context->customer->phone ?? $context->user->phone ?? null;

        if (! $phone) {
            return null;
        }

        // Customer has no country_code column of its own — only User does —
        // so fall back through the linked account when context only carries a Customer.
        $countryCode = $context->customer->country_code
            ?? $context->customer->user->country_code
            ?? $context->user->country_code
            ?? null;

        $e164 = ltrim(PhoneNumber::display($phone, $countryCode), '+');

        return hash('sha256', $e164);
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
