<?php

namespace App\OrderIntake;

use Closure;

/**
 * Turns a raw draft — from TextOrderParser or from the AI fallback — into
 * a resolved order draft: catalogue products/variants/prices, a valid
 * phone, the existing customer, a delivery zone and charge (the bulk
 * sheet's own ShippingCalculator quote), the discount (stated, percent, or
 * worked out from a stated total/COD amount) and the totals. Every field
 * says where it came from and how sure it is, and needsAi lists what the
 * deterministic pass couldn't settle.
 *
 * Nothing the AI says is trusted as-is: its product text/code goes through
 * the same catalogue matching, its numbers only as "stated" amounts that
 * the totals are checked against.
 *
 * @phpstan-import-type RawDraft from TextOrderParser
 */
final class DraftResolver
{
    /** Below this a field isn't taken as resolved. */
    public const SURE = 0.6;

    private const MATCH_CONFIDENCE = ['exact' => 1.0, 'code' => 0.95, 'name' => 0.85, 'price' => 0.8, 'context' => 0.75, 'keyword' => 0.85, 'partial' => 0.75, 'ai' => 0.75, 'fuzzy' => 0.45];

    /**
     * @param  Closure(int $methodId, list<array{product_id: int, quantity: float}> $items, float $subtotal): ?float  $quote
     * @param  array<string, array<string, mixed>>  $customers  national phone → BulkOrderCreate::lookupCustomers()-style entry
     */
    public function __construct(
        private Catalog $catalog,
        private DeliveryAreas $areas,
        private Closure $quote,
        private array $customers = [],
        private ?int $defaultMethodId = null,
    ) {}

    /**
     * @param  RawDraft  $raw
     * @return array<string, mixed>
     */
    public function resolve(array $raw): array
    {
        $issues = [];
        $needsAi = [];
        $sources = $raw['sources'] ?? [];
        $src = fn (string $field, string $default = 'parser') => $sources[$field] ?? $default;

        // Phone.
        $national = $raw['phone'] !== null ? PhoneExtractor::national($raw['phone']) : null;
        $phoneOk = $national !== null && PhoneExtractor::isValid($national);
        $phone = [
            'value'      => $national,
            'display'    => $national !== null ? PhoneExtractor::local($national) : null,
            'confidence' => $phoneOk ? ($src('phone') === 'ai' ? 0.8 : 1.0) : 0.0,
            'source'     => $src('phone'),
        ];
        if (! $phoneOk) {
            $needsAi[] = 'phone';
            $issues[] = $this->issue('error', 'phone', $national === null ? 'No phone number found' : 'Phone number is not a valid BD mobile number');
        }

        $customer = $phoneOk ? ($this->customers[$national] ?? null) : null;
        $existing = (bool) ($customer['found'] ?? false);

        // Name — only required for a new customer.
        $nameConf = $raw['name'] !== null ? (float) $raw['name_conf'] : 0.0;
        $name = ['value' => $raw['name'], 'confidence' => $nameConf, 'source' => $src('name')];
        if ($existing && ($raw['name'] === null || $nameConf < self::SURE)) {
            $name = ['value' => $raw['name'] ?? $customer['name'], 'confidence' => 1.0, 'source' => 'customer'];
        } elseif ($raw['name'] === null || $nameConf < self::SURE) {
            $needsAi[] = 'name';
            $issues[] = $this->issue('error', 'name', 'Customer name not found');
        } elseif ($nameConf < 0.9) {
            $issues[] = $this->issue('warning', 'name', $src('name') === 'ai'
                ? "Name \"{$raw['name']}\" read by AI — check it"
                : "Name \"{$raw['name']}\" taken from an unlabelled line — check it");
        }

        // Address — the customer's saved one covers a missing address.
        $addrConf = $raw['address'] !== null ? (float) $raw['address_conf'] : 0.0;
        $address = ['value' => $raw['address'], 'confidence' => $addrConf, 'source' => $src('address')];
        if (($raw['address'] === null || $addrConf < self::SURE) && $existing && ! empty($customer['address'])) {
            $address = ['value' => $raw['address'], 'confidence' => 1.0, 'source' => 'customer'];
            $issues[] = $this->issue('info', 'address', "Using the customer's saved address: {$customer['address']}");
        } elseif ($raw['address'] === null || $addrConf < self::SURE) {
            $needsAi[] = 'address';
            $issues[] = $this->issue('error', 'address', 'Delivery address not found');
        }

        // Items.
        $items = [];
        foreach ($raw['items'] as $rawItem) {
            $items[] = $this->resolveItem($rawItem, $src('items'));
        }
        if ($items === []) {
            $needsAi[] = 'products';
            $issues[] = $this->issue('error', 'products', 'No product found in the message');
        }
        foreach ($items as $it) {
            if ($it['issue'] !== null) {
                $issues[] = $this->issue($it['confidence'] < self::SURE ? 'error' : 'warning', 'products', $it['issue']);
            }
            if ($it['confidence'] < self::SURE) {
                $needsAi[] = 'products';
            }
        }

        // Delivery zone.
        $addressForArea = $address['value'] ?? ($address['source'] === 'customer' ? (string) ($customer['address'] ?? '') : '');
        $area = $this->areas->resolve(trim($addressForArea . ' ' . ($raw['area_hint'] ?? '')), $raw['source_text'] ?? '');
        if ($area['method_id'] === null && ! empty($raw['zone_hint'])) {
            $hinted = collect($this->areas->zones())->first(fn ($z) => Text::norm($z['zone']) === Text::norm($raw['zone_hint']));
            if ($hinted) {
                $area = ['method_id' => $hinted['method_id'], 'zone' => $hinted['label'], 'area' => $raw['zone_hint'], 'confidence' => 0.75, 'source' => 'ai', 'note' => 'Delivery zone picked by AI — check'];
            }
        }
        if ($area['method_id'] === null && $this->areas->zones() !== []) {
            // Not an AI reason: the zone is one click on the sheet, and the
            // AI rarely knows a place the district/thana lists don't.
            $default = collect($this->areas->zones())->first(fn ($z) => $z['method_id'] === $this->defaultMethodId) ?? collect($this->areas->zones())->first(fn ($z) => $z['is_default']);
            $area = ['method_id' => $default['method_id'] ?? null, 'zone' => $default['label'] ?? null, 'area' => null, 'confidence' => 0.3, 'source' => 'default', 'note' => null];
            $issues[] = $this->issue('warning', 'area', 'Delivery area not recognised' . ($default ? " — {$default['label']} (default) used" : ''));
        } elseif ($area['note']) {
            $issues[] = $this->issue('warning', 'area', $area['note']);
        }

        // Money.
        $okItems = array_values(array_filter($items, fn ($i) => $i['product_id'] !== null && $i['confidence'] >= self::SURE));
        $subtotal = round(array_sum(array_map(fn ($i) => $i['qty'] * $i['unit_price'], $okItems)), 2);

        $deliverySource = 'none';
        $delivery = null;
        if ($raw['delivery_charge'] !== null) {
            $delivery = max(0.0, (float) $raw['delivery_charge']);
            $deliverySource = 'stated';
        } elseif ($area['method_id'] && $okItems) {
            $delivery = ($this->quote)((int) $area['method_id'], array_map(fn ($i) => ['product_id' => $i['product_id'], 'quantity' => $i['qty']], $okItems), $subtotal);
            $deliverySource = $delivery !== null ? 'quote' : 'none';
        }

        $advance = $raw['advance'] !== null ? max(0.0, round((float) $raw['advance'], 2)) : 0.0;
        [$discount, $discountNote, $discountIssue] = $this->discount($raw, $subtotal, $delivery, $advance, count($okItems) === count($items));
        if ($discountIssue) {
            $issues[] = $discountIssue;
        }

        $total = max(0.0, round($subtotal + ($delivery ?? 0) - $discount, 2));

        if ($advance > $total && $total > 0) {
            $issues[] = $this->issue('warning', 'advance', 'Advance is more than the order total');
        }

        $critical = array_merge(
            [$phone['confidence'], $name['confidence'], $address['confidence']],
            $items ? array_column($items, 'confidence') : [0.0],
        );

        $needsAi = array_values(array_unique($needsAi));

        return [
            'via'         => in_array('ai', $sources, true) ? 'ai' : 'parser',
            'phone'       => $phone,
            'alt_phone'   => $raw['alt_phone'] ? PhoneExtractor::local(PhoneExtractor::national($raw['alt_phone'])) : null,
            'name'        => $name,
            'address'     => $address,
            'area'        => $area,
            'items'       => $items,
            'customer'    => $customer,
            'amounts'     => [
                'subtotal'        => $subtotal,
                'delivery'        => $delivery,
                'delivery_source' => $deliverySource,
                'discount'        => $discount,
                'discount_note'   => $discountNote,
                'total'           => $total,
                'advance'         => $advance,
                'due'             => max(0.0, round($total - $advance, 2)),
            ],
            'payment'     => $raw['payment'],
            'note'        => $raw['note'],
            'issues'      => $issues,
            'needs_ai'    => $needsAi,
            // Lines nothing was read from (parser drafts) — null for AI drafts.
            'unused'      => $raw['unused'] ?? null,
            'confidence'  => round(min($critical), 2),
            'ready'       => $needsAi === [] && ! array_filter($issues, fn ($i) => $i['level'] === 'error'),
            'source_text' => $raw['source_text'] ?? '',
        ];
    }

    /** @return array<string, mixed> */
    private function resolveItem(array $raw, string $source): array
    {
        $hit = null;

        if (! empty($raw['product_id']) && ($p = $this->catalog->product((int) $raw['product_id']))) {
            $hit = ['product' => $p, 'variant' => $this->catalog->variant($p['id'], $raw['variant_id'] ?? null), 'hint' => '', 'match' => $raw['match'] ?? 'exact'];
        } elseif (! empty($raw['code']) && ($byCode = $this->catalog->resolve((string) $raw['code'])) && ! isset($byCode['ambiguous']) && $byCode['match'] === 'exact') {
            // The AI picked one of the candidate codes it was shown.
            $hit = [...$byCode, 'match' => 'ai'];
        } else {
            $hit = $this->catalog->resolve((string) $raw['text']);
        }

        $qty = $raw['qty'] !== null && (float) $raw['qty'] > 0 ? (float) $raw['qty'] : null;
        // What the customer actually wrote — a parser mention's text is already the code.
        $said = (string) ($raw['said'] ?? $raw['text']);
        $base = [
            'text' => (string) $raw['text'], 'product_id' => null, 'variant_id' => null, 'product_name' => null, 'variant_label' => null,
            'code' => null, 'variant_text' => $raw['variant_text'] ?? null, 'variable' => false,
            'qty' => $qty ?? 1.0, 'qty_source' => $qty !== null ? $source : 'default',
            'unit_price' => 0.0, 'catalog_price' => 0.0, 'price_source' => 'catalog', 'match' => null, 'confidence' => 0.0, 'issue' => null, 'source' => $source,
            // Product ids the review screen offers for this line ("did you mean").
            'options' => [],
        ];

        if ($hit === null) {
            return [
                ...$base,
                'issue'   => "\"{$said}\" — product not found",
                'options' => array_column($this->catalog->candidates($said, 5, false), 'id'),
            ];
        }
        if (isset($hit['ambiguous'])) {
            $options = $hit['options'];
            $candidates = $hit['candidates'];
            $hit = $this->settleTie($candidates, $raw);

            if ($hit === null) {
                return [
                    ...$base,
                    'issue'   => "\"{$said}\" matches several products: " . implode(', ', $options),
                    'options' => array_values(array_unique(array_map(fn ($c) => $c['product']['id'], $candidates))),
                ];
            }
        }

        $product = $hit['product'];
        $variant = $hit['variant'];
        $variantIssue = null;

        $variantNote = null;

        if (! $variant && $product['variable']) {
            $picked = $this->catalog->pickVariant($product, trim(($hit['hint'] ?? '') . ' ' . ($raw['variant_text'] ?? '')));
            $variant = $picked['variant'];
            $variantIssue = $picked['problem'];

            // Size/colour on another line of the message.
            if (! $variant && ! empty($raw['variant_context'])) {
                $again = $this->catalog->pickVariant($product, (string) $raw['variant_context']);
                if ($again['variant']) {
                    [$variant, $variantIssue] = [$again['variant'], null];
                    $variantNote = "{$product['name']}: {$variant['label']} taken from elsewhere in the message";
                }
            }

            // The price quoted fits one variant only.
            $quoted = $raw['price'] ?? $raw['price_hint'] ?? null;
            if (! $variant && $quoted) {
                $byPrice = array_values(array_filter($product['variants'], fn ($v) => abs($v['price'] - (float) $quoted) < 0.01));
                if (count($byPrice) === 1) {
                    [$variant, $variantIssue] = [$byPrice[0], null];
                    $variantNote = "{$product['name']}: {$variant['label']} picked by the quoted price " . self::money((float) $quoted);
                }
            }
        }

        $catalogPrice = $this->catalog->priceOf($product['id'], $variant['id'] ?? null);
        $stated = $raw['price'] !== null && (float) $raw['price'] > 0 ? round((float) $raw['price'], 2) : null;
        $confidence = self::MATCH_CONFIDENCE[$hit['match']] ?? 0.5;

        if ($source === 'ai') {
            $confidence = min($confidence, 0.8);
        }

        $issue = match (true) {
            $variantIssue !== null      => "{$product['name']}: {$variantIssue}",
            $hit['match'] === 'fuzzy'   => "\"{$said}\" looks like {$product['name']} (spelling differs) — confirm it",
            $hit['match'] === 'partial' => "\"{$said}\" matched by partial name → {$product['name']}",
            $hit['match'] === 'ai'      => "Product picked by AI → {$product['name']} — check it",
            ! in_array($hit['match'], ['exact', 'code'], true) => "\"{$said}\" matched by name → {$product['name']}",
            $hit['match'] === 'price'   => "\"{$said}\" matched several products — {$product['name']} picked by the quoted price",
            $hit['match'] === 'context' => "\"{$said}\" matched several products — {$product['name']} picked from the rest of the message",
            $variantNote !== null       => $variantNote,
            $qty === null               => "Quantity not stated for {$product['name']} — 1 assumed",
            $stated !== null && abs($stated - $catalogPrice) >= 0.01 => "{$product['name']}: price " . self::money($stated) . ' from the message (catalogue ' . self::money($catalogPrice) . ')',
            default                     => null,
        };

        return [
            ...$base,
            'product_id'    => $product['id'],
            'variant_id'    => $variant['id'] ?? null,
            'product_name'  => $product['name'],
            'variable'      => $product['variable'],
            'variant_label' => $variant['label'] ?? null,
            'code'          => $variant['sku'] ?? null ?: ($product['code'] ?: null),
            'unit_price'    => $stated ?? $catalogPrice,
            'catalog_price' => $catalogPrice,
            'price_source'  => $stated !== null ? 'stated' : 'catalog',
            'match'         => $hit['match'],
            'confidence'    => $variantIssue !== null ? 0.0 : $confidence,
            'issue'         => $issue,
        ];
    }

    /**
     * Several products fit the text equally well: the one whose price is
     * the price quoted in the message, else the one whose distinctive words
     * show up elsewhere in the message — or null (never a guess).
     *
     * @param  list<array{product: array, hint: string, match: string}>  $candidates
     * @return array{product: array, variant: ?array, hint: string, match: string}|null
     */
    private function settleTie(array $candidates, array $raw): ?array
    {
        $quoted = $raw['price'] ?? $raw['price_hint'] ?? null;

        if ($quoted) {
            $byPrice = array_values(array_filter($candidates, function ($c) use ($quoted) {
                $prices = [(float) $c['product']['price'], ...array_column($c['product']['variants'], 'price')];

                return (bool) array_filter($prices, fn ($pr) => abs($pr - (float) $quoted) < 0.01);
            }));
            if (count($byPrice) === 1) {
                return ['product' => $byPrice[0]['product'], 'variant' => null, 'hint' => $byPrice[0]['hint'], 'match' => 'price'];
            }
        }

        $context = Text::words(($raw['variant_context'] ?? '') . ' ' . ($raw['variant_text'] ?? ''));
        if ($context !== []) {
            $scored = array_map(function ($c) use ($context, $candidates) {
                // Words of this name that the other candidates don't have.
                $others = array_merge(...array_map(fn ($o) => $o['product']['id'] === $c['product']['id'] ? [] : Text::words($o['product']['name']), $candidates));
                $own = array_diff(Text::words($c['product']['name']), $others);

                return [$c, count(array_intersect($own, $context))];
            }, $candidates);
            usort($scored, fn ($a, $b) => $b[1] <=> $a[1]);

            if ($scored[0][1] > 0 && ($scored[1][1] ?? 0) < $scored[0][1]) {
                return ['product' => $scored[0][0]['product'], 'variant' => null, 'hint' => $scored[0][0]['hint'], 'match' => 'context'];
            }
        }

        return null;
    }

    /**
     * The discount amount: stated outright (amount or percent of the
     * subtotal), or — when the customer/seller stated what's to be paid —
     * the gap between that and subtotal + delivery.
     *
     * @return array{0: float, 1: ?string, 2: ?array}
     */
    private function discount(array $raw, float $subtotal, ?float $delivery, float $advance, bool $allItemsResolved): array
    {
        $d = $raw['discount'];
        $stated = $raw['stated_total'];
        $discount = 0.0;
        $note = null;

        if ($d && $d['value'] > 0) {
            $discount = $d['type'] === 'percent' ? round($subtotal * $d['value'] / 100, 2) : round((float) $d['value'], 2);
            $note = $d['type'] === 'percent' ? "{$d['value']}% of " . self::money($subtotal) : 'stated';
        }

        if ($discount > $subtotal + ($delivery ?? 0)) {
            return [0.0, null, $this->issue('error', 'discount', 'Discount ' . self::money($discount) . ' is more than the order total')];
        }

        if (! $stated || $stated['value'] <= 0 || $subtotal <= 0 || ! $allItemsResolved) {
            return [$discount, $note, null];
        }

        $payable = $stated['kind'] === 'cod' ? $stated['value'] + $advance : $stated['value'];
        $label = $stated['kind'] === 'cod' ? 'COD amount' : 'stated total';

        if ($delivery === null) {
            return [$discount, $note, $this->issue('warning', 'discount', ucfirst($label) . ' ' . self::money($stated['value']) . ' — delivery charge unknown, so it can\'t be checked')];
        }

        $calculated = $subtotal + $delivery - $discount;
        $gap = round($calculated - $payable, 2);

        if (abs($gap) < 1) {
            return [$discount, $note, null];
        }

        // Explicit discount given and the totals still disagree — flag, don't change.
        if ($discount > 0) {
            return [$discount, $note, $this->issue('warning', 'discount', ucfirst($label) . ' ' . self::money($stated['value']) . ' doesn\'t match the calculated ' . self::money($calculated) . ' — check prices/discount')];
        }

        // A stated total equal to the products' price alone — delivery comes on
        // top, no discount. (A COD amount is what the courier collects, so it
        // already includes delivery.)
        if ($stated['kind'] === 'total' && abs($payable - $subtotal) < 1) {
            return [0.0, null, $this->issue('info', 'discount', ucfirst($label) . ' ' . self::money($stated['value']) . ' is the products\' price; delivery ' . self::money($delivery) . ' added on top')];
        }

        if ($gap > 0 && $gap <= $subtotal * 0.5) {
            return [$gap, "from {$label} " . self::money($stated['value']), $this->issue('warning', 'discount', 'Discount ' . self::money($gap) . ' worked out from the ' . $label . ' ' . self::money($stated['value']) . ' — check it')];
        }

        return [0.0, null, $this->issue('warning', 'discount', ucfirst($label) . ' ' . self::money($stated['value']) . ' doesn\'t match the calculated ' . self::money($calculated) . ' — check prices/delivery')];
    }

    /** @return array{level: string, field: string, message: string} */
    private function issue(string $level, string $field, string $message): array
    {
        return ['level' => $level, 'field' => $field, 'message' => $message];
    }

    private static function money(float $v): string
    {
        return '৳' . rtrim(rtrim(number_format($v, 2), '0'), '.');
    }

    /**
     * The bulk sheet row for a resolved draft — the cells the sheet parses
     * like typed input (product codes/SKUs so it resolves them exactly), and
     * the intake meta it shows next to the row.
     *
     * @param  array<string, mixed>  $draft
     * @return array{cells: array<string, string>, meta: array<string, mixed>}
     */
    public static function toSheetRow(array $draft, string $source, int $intakeId): array
    {
        $tokens = array_map(function ($it) {
            $ref = $it['product_id'] !== null
                ? ($it['code'] ?? $it['product_name']) . ($it['variable'] && $it['variant_id'] === null && $it['variant_text'] ? " {$it['variant_text']}" : '')
                : $it['text'];
            $qty = rtrim(rtrim(number_format((float) $it['qty'], 3, '.', ''), '0'), '.');
            $price = $it['product_id'] !== null && $it['price_source'] === 'stated' && abs($it['unit_price'] - $it['catalog_price']) >= 0.01 ? " @{$it['unit_price']}" : '';

            return trim(str_replace([',', ';', '|'], ' ', $ref)) . " x{$qty}{$price}";
        }, $draft['items']);

        $amounts = $draft['amounts'];
        // The sheet re-checks phone/name/address/products itself; the rest only we know.
        $sheetIssues = array_values(array_filter($draft['issues'],
            fn ($i) => $i['level'] !== 'error' || ! in_array($i['field'], ['phone', 'name', 'address', 'products'], true)));

        return [
            'cells' => [
                'phone'    => (string) ($draft['phone']['display'] ?? ''),
                'name'     => $draft['name']['source'] === 'customer' ? '' : (string) ($draft['name']['value'] ?? ''),
                'address'  => $draft['address']['source'] === 'customer' ? '' : (string) ($draft['address']['value'] ?? ''),
                'products' => implode(', ', $tokens),
                'qty'      => '',
                'price'    => '',
                'method'   => $draft['area']['method_id'] ? (string) $draft['area']['method_id'] : '',
                'delivery' => $amounts['delivery_source'] === 'stated' ? (string) $amounts['delivery'] : '',
                'discount' => $amounts['discount'] > 0 ? (string) $amounts['discount'] : '',
                'advance'  => $amounts['advance'] > 0 ? (string) $amounts['advance'] : '',
                'source'   => $source,
                'note'     => trim(implode(' | ', array_filter([$draft['note'], $draft['alt_phone'] ? "Alt phone {$draft['alt_phone']}" : null]))),
            ],
            'meta' => [
                'intakeId'   => $intakeId,
                'via'        => $draft['via'],
                'confidence' => $draft['confidence'],
                'issues'     => array_values(array_map(fn ($i) => $i['message'], $sheetIssues)),
                'issueFields' => array_values(array_map(fn ($i) => $i['field'], $sheetIssues)),
                'needsAi'    => $draft['needs_ai'],
                'sourceText' => mb_substr((string) $draft['source_text'], 0, 4000),
            ],
        ];
    }
}
