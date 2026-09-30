<?php

namespace App\Marketing\Destinations\Meta;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Events\AddToCart;
use App\Marketing\Events\InitiateCheckout;
use App\Marketing\Events\Purchase;
use App\Marketing\Events\ViewContent;
use App\Marketing\Identity\IdentityResolver;
use App\Marketing\Identity\MarketingIdentity;

/**
 * Normalization + hashing rules follow Meta's customer information
 * parameters doc:
 * https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
 */
final class MetaPayloadBuilder
{
    public function __construct(
        private readonly IdentityResolver $identityResolver,
    ) {}

    public function build(
        EventContract $event,
        MarketingContext $context,
    ): array {
        return [
            'data' => [
                // [] is dropped too: an event with no custom data (PageView)
                // would otherwise encode custom_data as a JSON array, not an
                // object.
                array_filter([
                    'event_name' => $event->eventName(),
                    'event_time' => $event->occurredAt()->timestamp,
                    'event_id' => $event->eventId(),
                    'event_source_url' => $context->pageUrl,
                    'action_source' => 'website',
                    'user_data' => $this->buildUserData($event, $context),
                    'custom_data' => $this->buildCustomData($event),
                ], fn ($value) => $value !== null && $value !== []),
            ],
        ];
    }

    private function buildUserData(
        EventContract $event,
        MarketingContext $context,
    ): array {
        $identity = $this->identityResolver->resolve($context, $event);

        return array_filter([
            // Never hashed, per Meta.
            'client_ip_address' => $context->ipAddress,
            'client_user_agent' => $context->userAgent,
            'fbp' => $context->trackingCookies[MetaBrowserCookies::FBP] ?? null,
            'fbc' => $context->trackingCookies[MetaBrowserCookies::FBC] ?? null,

            'external_id' => array_map($this->hash(...), $identity->externalIds) ?: null,

            'em' => $this->hash($this->normalizeEmail($identity->email)),
            'ph' => $this->hash($this->normalizePhone($identity->phone)),
            'fn' => $this->hash($this->normalizeText($identity->firstName)),
            'ln' => $this->hash($this->normalizeText($identity->lastName)),
            'ge' => $this->hash($this->normalizeGender($identity->gender)),
            'db' => $this->hash($this->normalizeDateOfBirth($identity)),
            'ct' => $this->hash($this->normalizeCity($identity->city)),
            'st' => $this->hash($this->normalizeText($identity->state)),
            'zp' => $this->hash($this->normalizeZip($identity->zip)),
            'country' => $this->hash($this->normalizeCountry($identity->country)),
        ], fn ($value) => $value !== null);
    }

    private function hash(?string $value): ?string
    {
        return $value === null || $value === '' ? null : hash('sha256', $value);
    }

    /**
     * Phone-only registrations and guest checkouts get an auto-generated
     * placeholder address (user+xxxx@<app-host>, guest+<phone>@<host>) since
     * this storefront doesn't require a real email — those aren't the
     * customer's actual email and must never be sent to Meta as if they were.
     */
    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || str_starts_with($email, 'user+') || str_starts_with($email, 'guest+')) {
            return null;
        }

        return $email;
    }

    /** Digits only, country code first, no leading zeros: 8801761234567. */
    private function normalizePhone(?string $phone): ?string
    {
        return ltrim(preg_replace('/\D/', '', (string) $phone), '0') ?: null;
    }

    /**
     * fn/ln/st: lowercase, no punctuation, no spaces. Non-Latin letters
     * (e.g. a name typed in Bangla) are kept — Meta accepts UTF-8.
     */
    private function normalizeText(?string $value): ?string
    {
        return preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '', mb_strtolower(trim((string) $value))) ?: null;
    }

    /**
     * Seeded Bangladeshi cities are upazila names ("Dhaka Metropolitan",
     * "Narsingdi Sadar"); the administrative suffix isn't part of the city
     * name people (and Meta profiles) actually use.
     */
    private function normalizeCity(?string $city): ?string
    {
        return $this->normalizeText(preg_replace('/\s+(metropolitan|sadar)$/iu', '', trim((string) $city)));
    }

    /** Only f/m — anything else is omitted rather than guessed. */
    private function normalizeGender(?string $gender): ?string
    {
        $initial = strtolower(substr(trim((string) $gender), 0, 1));

        return in_array($initial, ['f', 'm'], true) ? $initial : null;
    }

    /** YYYYMMDD */
    private function normalizeDateOfBirth(MarketingIdentity $identity): ?string
    {
        return $identity->dateOfBirth ? str_replace('-', '', $identity->dateOfBirth) : null;
    }

    private function normalizeZip(?string $zip): ?string
    {
        return preg_replace('/[\s-]+/', '', strtolower(trim((string) $zip))) ?: null;
    }

    /** ISO 3166-1 alpha-2, lowercase. */
    private function normalizeCountry(?string $country): ?string
    {
        $country = strtolower(trim((string) $country));

        return preg_match('/^[a-z]{2}$/', $country) ? $country : null;
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
        return $this->withoutEmpty([
            'value' => $event->value,
            'currency' => $event->currency,
            'order_id' => $event->orderId,
            'contents' => $this->contents($event->items),
            'content_ids' => $this->contentIds($event->items),
            'content_type' => 'product',
        ]);
    }

    /**
     * content_ids / content_type must match the catalog (see
     * CatalogItemId): ViewContent of a variable product carries its item
     * group id as product_group; everything else is a single item. Advantage+
     * catalog ads require contents on AddToCart.
     */
    private function buildContentData(
        ViewContent|AddToCart $event,
    ): array {
        $id = $event->contentId !== null ? (string) $event->contentId : null;

        $contents = $event instanceof AddToCart && $id !== null
            ? [$this->withoutEmpty([
                'id' => $id,
                'quantity' => $event->quantity,
                'item_price' => $event->value !== null && $event->quantity > 0 ? round($event->value / $event->quantity, 2) : null,
            ])]
            : null;

        return $this->withoutEmpty([
            'value' => $event->value,
            'currency' => $event->currency,
            'content_ids' => $id !== null ? [$id] : null,
            'content_name' => $event->contentName,
            'content_type' => $event instanceof ViewContent ? ($event->contentType ?? 'product') : 'product',
            'contents' => $contents,
        ]);
    }

    private function buildCheckoutData(
        InitiateCheckout $event,
    ): array {
        return $this->withoutEmpty([
            'value' => $event->value,
            'currency' => $event->currency,
            'contents' => $this->contents($event->items),
            'content_ids' => $this->contentIds($event->items),
            'content_type' => 'product',
            'num_items' => $event->itemCount ?? count($event->items),
        ]);
    }

    /**
     * Meta's contents shape — only id, integer quantity and item_price.
     * Cart/order quantities are decimal columns ("1.000"), so they're cast.
     */
    private function contents(array $items): array
    {
        return array_map(
            fn (array $item) => $this->withoutEmpty([
                'id' => $item['item_id'] ?? null,
                'quantity' => isset($item['quantity']) ? (int) round((float) $item['quantity']) : null,
                'item_price' => isset($item['price']) ? (float) $item['price'] : null,
            ]),
            $items,
        );
    }

    private function contentIds(array $items): array
    {
        return array_values(array_filter(array_column($items, 'item_id')));
    }

    /** Drops null and [] only — a legitimate 0 (value of a fully discounted order) stays. */
    private function withoutEmpty(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== []);
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
}
