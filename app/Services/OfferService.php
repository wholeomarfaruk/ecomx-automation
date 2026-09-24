<?php

namespace App\Services;

use App\Enums\Sales\ConditionOperator;
use App\Enums\Sales\ConditionType;
use App\Enums\Sales\DiscountRuleType;
use App\Enums\Sales\PromotionType;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionCondition;
use App\Models\PromotionDiscountRule;
use Illuminate\Support\Collection;

/**
 * Applies admin-configured Offers (promotions.type = offer) to a storefront
 * cart. Shared by every theme's cart drawer and checkout, so the discount a
 * customer sees is exactly what their order is saved with.
 *
 * How an offer is evaluated:
 *   1. Active only — status active and now within starts_at/ends_at
 *      (Promotion::active()).
 *   2. Eligible lines — cart lines matching the offer's Target Products
 *      (promotion_items; empty = every line), further narrowed by any
 *      line-level conditions: product, variant, category, brand.
 *   3. Cart-level conditions must all hold: cart_amount (whole cart
 *      subtotal), quantity (eligible units), customer, customer_group,
 *      payment_method, shipping_method (the checkout delivery area, e.g.
 *      "dhaka"/"outside"). A condition whose input isn't known yet (e.g.
 *      payment method while still in the cart drawer) counts as not met.
 *   4. Every discount rule on the offer is applied to the eligible lines
 *      (see applyRule()); max_discount_amount caps each rule.
 *
 * Priority/stacking: offers are tried highest priority first. The first
 * offer that yields a discount is applied; if it isn't stackable, nothing
 * else is. Later offers are only added while every applied offer so far —
 * and the candidate itself — is stackable. Each offer discounts only what
 * earlier offers left of a line, so lines can never go below zero.
 *
 * Condition values are matched against ids, and for category/brand also
 * against name/slug (case-insensitive), so admins can type either.
 */
class OfferService
{
    protected ?Collection $activeOffers = null;

    /** @var array<int, list<array{name: string, label: string}>> product id => badges, memoized per request */
    protected array $productBadges = [];

    /** @var array<string, float> "product|variant|selling" => unit price after offers, memoized per request */
    protected array $unitPrices = [];

    /**
     * Rule types whose effect on a single unit is fixed regardless of
     * quantity — the only ones folded into a displayed per-unit price
     * (Product::unitPricing()). Fixed order-amount, buy-x-get-y, free item
     * and free shipping depend on the whole cart, so they stay checkout-only
     * (still advertised via badges).
     */
    protected const PER_UNIT_RULES = [DiscountRuleType::PERCENTAGE, DiscountRuleType::FIXED_PRICE];

    /** Active offers with everything evaluation needs, highest priority first (memoized per request). */
    public function activeOffers(): Collection
    {
        return $this->activeOffers ??= Promotion::active()
            ->where('type', PromotionType::OFFER)
            ->whereHas('offer')
            ->with('offer', 'conditions', 'discountRules', 'items')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param array{customer?: ?Customer, payment_method?: ?string, shipping_method?: ?string, shipping_amount?: float} $context
     * @return array{
     *     discount: float,
     *     shipping_discount: float,
     *     lines: array<int, float>,
     *     applied: list<array{promotion_id: int, offer_id: int, name: string, discount: float, shipping_discount: float}>
     * }
     *   lines = offer discount per cart_item id.
     */
    public function evaluate(Cart $cart, array $context = []): array
    {
        $result = ['discount' => 0.0, 'shipping_discount' => 0.0, 'lines' => [], 'applied' => []];

        $cart->loadMissing('items.product.categories', 'items.product.brand');

        $lines = $cart->items
            ->filter(fn ($item) => ! $item->is_gift && $item->product && (float) $item->quantity > 0)
            ->map(fn ($item) => [
                'id'         => $item->id,
                'product_id' => (int) $item->product_id,
                'variant_id' => $item->variant_id ? (int) $item->variant_id : null,
                'qty'        => (float) $item->quantity,
                'total'      => (float) $item->price * (float) $item->quantity,
                'product'    => $item->product,
            ])
            ->values();

        if ($lines->isEmpty()) {
            return $result;
        }

        return $this->applyOffers($lines, $context);
    }

    /**
     * Price of one unit after offers, for display (product cards, product
     * page) — exactly what OfferService::evaluate() would take off a cart
     * holding just this one unit with no checkout context, counting only
     * per-unit rules (PER_UNIT_RULES). Offers with cart-level conditions
     * (minimum spend, customer, payment, …) therefore don't lower it.
     */
    public function unitPrice(Product $product, float $selling, ?int $variantId = null): float
    {
        if ($selling <= 0 || $this->activeOffers()->isEmpty()) {
            return $selling;
        }

        $key = $product->id . '|' . ($variantId ?? '') . '|' . $selling;

        if (array_key_exists($key, $this->unitPrices)) {
            return $this->unitPrices[$key];
        }

        $line = [
            'id'         => 0,
            'product_id' => (int) $product->id,
            'variant_id' => $variantId,
            'qty'        => 1.0,
            'total'      => $selling,
            'product'    => $product,
        ];

        $result = $this->applyOffers(collect([$line]), [], self::PER_UNIT_RULES);

        return $this->unitPrices[$key] = max(0.0, round($selling - ($result['lines'][0] ?? 0.0), 2));
    }

    /**
     * The offer loop shared by evaluate() and unitPrice() — see the class
     * docblock for priority/stacking. $ruleTypes limits which discount rule
     * types are applied (null = all).
     *
     * @param Collection<int, array{id: int, product_id: int, variant_id: ?int, qty: float, total: float, product: Product}> $lines
     * @param list<DiscountRuleType>|null $ruleTypes
     */
    protected function applyOffers(Collection $lines, array $context, ?array $ruleTypes = null): array
    {
        $result = ['discount' => 0.0, 'shipping_discount' => 0.0, 'lines' => [], 'applied' => []];

        $cartSubtotal = (float) $lines->sum('total');
        $remaining = $lines->pluck('total', 'id')->all();
        $shippingLeft = max(0.0, (float) ($context['shipping_amount'] ?? 0));
        $allStackable = true;

        foreach ($this->activeOffers() as $promotion) {
            if ($result['applied'] !== [] && (! $allStackable || ! $promotion->stackable)) {
                continue;
            }

            $eligible = $lines->filter(fn ($line) => $this->lineEligible($promotion, $line))->values();

            if ($eligible->isEmpty() || ! $this->cartConditionsMet($promotion, $eligible, $cartSubtotal, $context)) {
                continue;
            }

            $lineDiscounts = [];
            $shippingDiscount = 0.0;

            foreach ($promotion->discountRules as $rule) {
                if ($ruleTypes !== null && ! in_array($rule->type, $ruleTypes, true)) {
                    continue;
                }

                if ($rule->type === DiscountRuleType::FREE_SHIPPING) {
                    $amount = $this->cap($shippingLeft - $shippingDiscount, $rule);
                    $shippingDiscount += max(0.0, $amount);

                    continue;
                }

                $available = [];
                foreach ($eligible as $line) {
                    $available[$line['id']] = max(0.0, $remaining[$line['id']] - ($lineDiscounts[$line['id']] ?? 0.0));
                }

                foreach ($this->applyRule($rule, $eligible, $available) as $lineId => $amount) {
                    $lineDiscounts[$lineId] = ($lineDiscounts[$lineId] ?? 0.0) + $amount;
                }
            }

            $lineDiscounts = array_map(fn ($d) => round($d, 2), array_filter($lineDiscounts, fn ($d) => $d > 0));
            $discount = round(array_sum($lineDiscounts), 2);
            $shippingDiscount = round($shippingDiscount, 2);

            if ($discount <= 0 && $shippingDiscount <= 0) {
                continue;
            }

            foreach ($lineDiscounts as $lineId => $amount) {
                $remaining[$lineId] -= $amount;
                $result['lines'][$lineId] = round(($result['lines'][$lineId] ?? 0.0) + $amount, 2);
            }

            $shippingLeft -= $shippingDiscount;
            $allStackable = $allStackable && $promotion->stackable;

            $result['discount'] = round($result['discount'] + $discount, 2);
            $result['shipping_discount'] = round($result['shipping_discount'] + $shippingDiscount, 2);
            $result['applied'][] = [
                'promotion_id'      => $promotion->id,
                'offer_id'          => $promotion->offer->id,
                'name'              => $promotion->name,
                'discount'          => $discount,
                'shipping_discount' => $shippingDiscount,
            ];
        }

        return $result;
    }

    /**
     * Active offers that can apply to this product — its Target Products
     * include it (or the offer targets everything) and its product/category/
     * brand conditions match. Cart-level conditions (amount, payment, …)
     * aren't known on a product page, so they don't filter here — the badge
     * label flags them instead (conditionHint()); checkout decides if it's earned.
     *
     * @return list<array{name: string, label: string}>
     */
    public function offersForProduct(Product $product): array
    {
        $product->loadMissing('categories', 'brand');

        $line = ['product_id' => (int) $product->id, 'variant_id' => null, 'product' => $product];

        return $this->activeOffers()
            ->filter(fn (Promotion $promotion) => $promotion->discountRules->isNotEmpty() && $this->lineEligible($promotion, $line, anyVariant: true))
            ->map(fn (Promotion $promotion) => $this->badge($promotion))
            ->values()
            ->all();
    }

    /**
     * offersForProduct() by id, for product cards that only carry a plain
     * array. Cheap when it can be: no active offers → no query; offers whose
     * Target Products exclude this product are ruled out without loading it;
     * the product (with categories/brand) is only fetched if an offer could
     * still apply. Memoized per product for the request.
     *
     * @return list<array{name: string, label: string}>
     */
    public function badgesForProductId(int $productId): array
    {
        if (array_key_exists($productId, $this->productBadges)) {
            return $this->productBadges[$productId];
        }

        $candidates = $this->activeOffers()->filter(fn (Promotion $promotion) => $promotion->discountRules->isNotEmpty()
            && ($promotion->items->isEmpty() || $promotion->items->contains(
                fn ($item) => $item->variant_id || (int) $item->product_id === $productId
            )));

        if ($candidates->isEmpty()) {
            return $this->productBadges[$productId] = [];
        }

        // The product row is only needed to check product/category/brand
        // conditions or a variant-specific target; otherwise the candidates
        // above already are the answer, with no query at all.
        $needsProduct = $candidates->contains(fn (Promotion $promotion) => $promotion->items->contains(fn ($item) => $item->variant_id)
            || $promotion->conditions->contains(fn (PromotionCondition $c) => $this->isLineCondition($c)));

        if (! $needsProduct) {
            return $this->productBadges[$productId] = $candidates->map(fn (Promotion $promotion) => $this->badge($promotion))->values()->all();
        }

        $product = Product::with('categories', 'brand')->find($productId);

        return $this->productBadges[$productId] = $product ? $this->offersForProduct($product) : [];
    }

    /** Short customer-facing summary of an offer's discount rules, e.g. "20% off", "Buy 2 Get 1 free + Free delivery". */
    public function label(Promotion $promotion): string
    {
        if ($promotion->discountRules->isEmpty()) {
            return $promotion->name;
        }

        return $promotion->discountRules->map(fn (PromotionDiscountRule $rule) => $this->ruleLabel($rule))->unique()->implode(' + ');
    }

    protected function ruleLabel(PromotionDiscountRule $rule): string
    {
        $value = $rule->value !== null ? (float) $rule->value : 0.0;
        $number = fn (float $n) => rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return match ($rule->type) {
            DiscountRuleType::PERCENTAGE    => $number($value) . '% off',
            DiscountRuleType::FIXED         => '৳' . $number($value) . ' off',
            DiscountRuleType::FIXED_PRICE   => 'Now ৳' . $number($value),
            DiscountRuleType::BUY_X_GET_Y   => 'Buy ' . ($rule->buy_quantity ?: 1) . ' Get ' . ($rule->get_quantity ?: 1)
                . ($value > 0 && $value < 100 ? ' at ' . $number($value) . '% off' : ' free'),
            DiscountRuleType::FREE_ITEM     => 'Free item',
            DiscountRuleType::FREE_SHIPPING => 'Free delivery',
        };
    }

    /** @return array{name: string, label: string} */
    protected function badge(Promotion $promotion): array
    {
        return [
            'name'  => $promotion->name,
            'label' => $this->label($promotion) . $this->conditionHint($promotion),
        ];
    }

    /** Product/variant/category/brand conditions narrow which lines an offer covers; the rest are cart-level. */
    protected function isLineCondition(PromotionCondition $condition): bool
    {
        return in_array(
            $condition->type,
            [ConditionType::PRODUCT, ConditionType::VARIANT, ConditionType::CATEGORY, ConditionType::BRAND],
            true
        );
    }

    /**
     * Badge suffix for cart-level conditions a product page can't check, so
     * a conditional offer isn't advertised as unconditional: a minimum
     * spend is spelled out, anything else gets a generic "conditions apply".
     */
    protected function conditionHint(Promotion $promotion): string
    {
        $cartLevel = $promotion->conditions->reject(fn (PromotionCondition $c) => $this->isLineCondition($c));

        if ($cartLevel->isEmpty()) {
            return '';
        }

        $minSpend = $cartLevel->first(fn (PromotionCondition $c) => $c->type === ConditionType::CART_AMOUNT
            && in_array($c->operator, [ConditionOperator::GREATER_THAN, ConditionOperator::GREATER_THAN_OR_EQUAL], true));

        return $minSpend && $cartLevel->count() === 1
            ? ' on orders ৳' . number_format((float) $minSpend->value_decoded) . '+'
            : ' (conditions apply)';
    }

    /**
     * @param array{product_id: int, variant_id: ?int, product: Product} $line
     * @param bool $anyVariant  Product-page mode: a variant-specific target matches if it's any variant of this product.
     */
    protected function lineEligible(Promotion $promotion, array $line, bool $anyVariant = false): bool
    {
        if ($promotion->items->isNotEmpty()) {
            $targeted = $promotion->items->contains(function ($item) use ($line, $anyVariant) {
                if ($item->variant_id) {
                    return $anyVariant
                        ? (int) $item->product_id === $line['product_id'] || $line['product']->variants()->whereKey($item->variant_id)->exists()
                        : (int) $item->variant_id === $line['variant_id'];
                }

                return (int) $item->product_id === $line['product_id'];
            });

            if (! $targeted) {
                return false;
            }
        }

        foreach ($promotion->conditions as $condition) {
            $identifiers = match ($condition->type) {
                ConditionType::PRODUCT  => [$line['product_id'], $line['product']->code, $line['product']->slug],
                ConditionType::VARIANT  => $anyVariant ? null : [$line['variant_id']],
                ConditionType::CATEGORY => $line['product']->categories
                    ->flatMap(fn ($c) => [$c->id, $c->name, $c->slug])->all(),
                ConditionType::BRAND    => $line['product']->brand
                    ? [$line['product']->brand->id, $line['product']->brand->name, $line['product']->brand->slug ?? null]
                    : [],
                default                 => null, // cart-level condition — checked in cartConditionsMet()
            };

            if ($identifiers !== null && ! $this->matches($condition, $identifiers)) {
                return false;
            }
        }

        return true;
    }

    protected function cartConditionsMet(Promotion $promotion, Collection $eligible, float $cartSubtotal, array $context): bool
    {
        /** @var ?Customer $customer */
        $customer = $context['customer'] ?? null;

        foreach ($promotion->conditions as $condition) {
            $met = match ($condition->type) {
                ConditionType::CART_AMOUNT     => $this->compareNumber($cartSubtotal, $condition),
                ConditionType::QUANTITY        => $this->compareNumber((float) $eligible->sum('qty'), $condition),
                ConditionType::CUSTOMER        => $customer !== null
                    && $this->matches($condition, [$customer->id, $customer->phone, $customer->customer_code]),
                ConditionType::CUSTOMER_GROUP  => $customer?->customer_group_id !== null
                    && $this->matches($condition, [$customer->customer_group_id, $customer->customerGroup?->name]),
                ConditionType::PAYMENT_METHOD  => ($context['payment_method'] ?? null) !== null
                    && $this->matches($condition, [$context['payment_method']]),
                ConditionType::SHIPPING_METHOD => ($context['shipping_method'] ?? null) !== null
                    && $this->matches($condition, [$context['shipping_method']]),
                default                        => true, // line-level — already applied in lineEligible()
            };

            if (! $met) {
                return false;
            }
        }

        return true;
    }

    /**
     * Discount per eligible line id for one rule, never more than what's
     * still available on that line.
     *
     * @param array<int, float> $available line id => amount still discountable
     * @return array<int, float>
     */
    protected function applyRule(PromotionDiscountRule $rule, Collection $eligible, array $available): array
    {
        $value = $rule->value !== null ? (float) $rule->value : 0.0;
        $pool = array_sum($available);

        if ($pool <= 0) {
            return [];
        }

        $discounts = match ($rule->type) {
            DiscountRuleType::PERCENTAGE => array_map(fn ($a) => $a * min(100.0, max(0.0, $value)) / 100, $available),

            // One fixed amount off the eligible lines, split proportionally.
            DiscountRuleType::FIXED => array_map(fn ($a) => $a / $pool * min($pool, max(0.0, $value)), $available),

            // Each eligible unit sells at $value.
            DiscountRuleType::FIXED_PRICE => $eligible->mapWithKeys(fn ($line) => [
                $line['id'] => max(0.0, $available[$line['id']] - $value * $line['qty']),
            ])->all(),

            // Every (buy + get) eligible units, the cheapest `get` units are
            // free — or $value% off when a value under 100 is set.
            DiscountRuleType::BUY_X_GET_Y => $this->discountCheapestUnits(
                $eligible,
                $available,
                (int) floor($this->unitCount($eligible) / (max(1, (int) $rule->buy_quantity) + max(1, (int) $rule->get_quantity)))
                    * max(1, (int) $rule->get_quantity),
                $value > 0 && $value < 100 ? $value : 100.0,
            ),

            // Once per order: the cheapest `get` (default 1) eligible units
            // are free, provided at least `buy` eligible units are in the cart.
            DiscountRuleType::FREE_ITEM => $this->unitCount($eligible) >= max(1, (int) $rule->buy_quantity)
                ? $this->discountCheapestUnits($eligible, $available, max(1, (int) $rule->get_quantity), 100.0)
                : [],

            default => [],
        };

        $total = array_sum($discounts);
        $capped = $this->cap($total, $rule);

        if ($total > 0 && $capped < $total) {
            $discounts = array_map(fn ($d) => $d / $total * $capped, $discounts);
        }

        return $discounts;
    }

    /** @return array<int, float> line id => discount for the $count cheapest eligible units at $percent% off */
    protected function discountCheapestUnits(Collection $eligible, array $available, int $count, float $percent): array
    {
        if ($count <= 0) {
            return [];
        }

        $units = [];
        foreach ($eligible as $line) {
            $wholeUnits = (int) floor($line['qty']);
            $unitPrice = $wholeUnits > 0 ? $available[$line['id']] / $line['qty'] : 0.0;

            for ($i = 0; $i < $wholeUnits; $i++) {
                $units[] = ['id' => $line['id'], 'price' => $unitPrice];
            }
        }

        usort($units, fn ($a, $b) => $a['price'] <=> $b['price']);

        $discounts = [];
        foreach (array_slice($units, 0, $count) as $unit) {
            $discounts[$unit['id']] = ($discounts[$unit['id']] ?? 0.0) + $unit['price'] * $percent / 100;
        }

        return $discounts;
    }

    protected function unitCount(Collection $eligible): int
    {
        return (int) $eligible->sum(fn ($line) => floor($line['qty']));
    }

    protected function cap(float $amount, PromotionDiscountRule $rule): float
    {
        return $rule->max_discount_amount !== null
            ? min($amount, (float) $rule->max_discount_amount)
            : $amount;
    }

    protected function compareNumber(float $actual, PromotionCondition $condition): bool
    {
        if ($condition->operator->isMultiValue()) {
            return $this->matches($condition, [$actual]);
        }

        $expected = (float) $condition->value_decoded;

        return match ($condition->operator) {
            ConditionOperator::EQUALS                => abs($actual - $expected) < 0.001,
            ConditionOperator::NOT_EQUALS            => abs($actual - $expected) >= 0.001,
            ConditionOperator::GREATER_THAN          => $actual > $expected,
            ConditionOperator::GREATER_THAN_OR_EQUAL => $actual >= $expected,
            ConditionOperator::LESS_THAN             => $actual < $expected,
            ConditionOperator::LESS_THAN_OR_EQUAL    => $actual <= $expected,
            default                                  => false,
        };
    }

    /**
     * Set-style match of a condition against a line/customer's identifiers
     * (ids, names, slugs, codes — compared case-insensitively as strings).
     * Numeric operators on identifier conditions compare the first identifier.
     */
    protected function matches(PromotionCondition $condition, array $identifiers): bool
    {
        $normalize = fn ($v) => mb_strtolower(trim((string) $v));
        $actual = array_values(array_filter(array_map($normalize, $identifiers), fn ($v) => $v !== ''));
        $expected = array_values(array_filter(array_map($normalize, (array) $condition->value_decoded), fn ($v) => $v !== ''));
        $hit = array_intersect($actual, $expected) !== [];

        return match ($condition->operator) {
            ConditionOperator::EQUALS, ConditionOperator::IN         => $hit,
            ConditionOperator::NOT_EQUALS, ConditionOperator::NOT_IN => ! $hit,
            default => $actual !== [] && is_numeric($actual[0]) && is_numeric($expected[0] ?? null)
                && $this->compareNumber((float) $actual[0], $condition),
        };
    }
}
