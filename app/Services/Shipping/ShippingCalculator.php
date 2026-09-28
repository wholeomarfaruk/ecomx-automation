<?php

namespace App\Services\Shipping;

use App\Enums\Sales\ShippingRateType;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

/**
 * Prices delivery for every storefront checkout from the zones and methods
 * managed under Settings → Shipping. Free-delivery coupons and campaigns are
 * not applied here — OfferService discounts the amount this returns.
 */
class ShippingCalculator
{
    /** Divides L×W×H (cm) into volumetric kg when a method doesn't set its own. */
    public const DEFAULT_VOLUMETRIC_DIVISOR = 5000;

    /**
     * Active zones that have at least one active method, each with only its
     * active methods loaded, in admin display order.
     *
     * @return Collection<int, ShippingZone>
     */
    public function zones(): Collection
    {
        return ShippingZone::query()
            ->where('is_active', true)
            ->whereHas('methods', fn ($q) => $q->where('is_active', true))
            ->with(['methods' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Cart items reduced to what shipping needs. Gift lines ship too, so
     * they count toward weight, but not toward the chargeable quantity.
     *
     * @return list<array{product: ?Product, quantity: float, is_gift: bool}>
     */
    public function cartLines(Cart $cart): array
    {
        $cart->loadMissing('items.product');

        return $cart->items->map(fn ($item) => [
            'product'  => $item->product,
            'quantity' => (float) $item->quantity,
            'is_gift'  => (bool) $item->is_gift,
        ])->all();
    }

    /**
     * @param  list<array{product: ?Product, quantity: float, is_gift: bool}>  $lines
     */
    public function quote(ShippingMethod $method, array $lines, float $subtotal): ShippingQuote
    {
        $type = $method->rate_type;
        // Rounded to grams so float sums (0.1 × 3 = 0.30000000000000004)
        // can't tip a cart into the next kg or band.
        $weight = round($this->weight($lines, $type->usesWeight() && ! empty($method->rate_config['volumetric']), $this->divisor($method)), 3);
        $quantity = (int) ceil(collect($lines)->reject(fn ($l) => $l['is_gift'])->sum('quantity'));

        $base = round(max(0.0, $this->baseCharge($method, $weight, $quantity, $subtotal)), 2);

        $freeOver = $method->free_over !== null ? (float) $method->free_over : null;
        $freeByThreshold = $freeOver !== null && $base > 0 && $subtotal >= $freeOver;
        $amount = $freeByThreshold ? 0.0 : $base;

        return new ShippingQuote(
            amount: $amount,
            baseAmount: $base,
            freeByThreshold: $freeByThreshold,
            freeOver: $freeOver,
            remainingForFree: $freeOver !== null && $amount > 0 ? round(max(0.0, $freeOver - $subtotal), 2) : 0.0,
            weight: $weight,
            quantity: $quantity,
        );
    }

    private function baseCharge(ShippingMethod $method, float $weight, int $quantity, float $subtotal): float
    {
        return match ($method->rate_type) {
            ShippingRateType::FLAT => $method->config('amount'),

            // Base charge covers the first base_weight kg; every started kg
            // above it adds per_kg.
            ShippingRateType::WEIGHT => $method->config('base_charge')
                + $this->startedUnits($weight - $method->config('base_weight', 1.0)) * $method->config('per_kg'),

            ShippingRateType::WEIGHT_BANDS => $this->bandCharge($method, $weight, $method->config('extra_per_kg')),

            ShippingRateType::QUANTITY => $quantity > 0
                ? $method->config('first_charge') + ($quantity - 1) * $method->config('additional_charge')
                : 0.0,

            ShippingRateType::SUBTOTAL_BANDS => $this->bandCharge($method, $subtotal, 0.0),

            ShippingRateType::PERCENTAGE => $this->percentageCharge($method, $subtotal),

            ShippingRateType::FREE => 0.0,
        };
    }

    /**
     * Charge of the first band whose "up to" covers $value; a band with no
     * "up to" is open-ended. Past the last closed band, its charge applies
     * plus $extraPerUnit for each started unit above it.
     */
    private function bandCharge(ShippingMethod $method, float $value, float $extraPerUnit): float
    {
        $bands = $this->bands($method);

        if ($bands === []) {
            return 0.0;
        }

        foreach ($bands as $band) {
            if ($band['up_to'] === null || $value <= $band['up_to']) {
                return $band['charge'];
            }
        }

        $last = end($bands);

        return $last['charge'] + $this->startedUnits($value - $last['up_to']) * $extraPerUnit;
    }

    /**
     * Whole units needed to cover $excess (1.2 → 2), 0 when none. The
     * difference is rounded first: 1.1 − 0.1 is 1.0000000000000002 in
     * floats, which a bare ceil() would bill as 2.
     */
    private function startedUnits(float $excess): float
    {
        return ceil(max(0.0, round($excess, 3)));
    }

    /**
     * rate_config.bands cleaned and sorted by "up to", open-ended band last.
     *
     * @return list<array{up_to: ?float, charge: float}>
     */
    public function bands(ShippingMethod $method): array
    {
        return collect($method->rate_config['bands'] ?? [])
            ->filter(fn ($b) => is_numeric($b['charge'] ?? null))
            ->map(fn ($b) => [
                'up_to'  => is_numeric($b['up_to'] ?? null) ? (float) $b['up_to'] : null,
                'charge' => (float) $b['charge'],
            ])
            ->sortBy(fn ($b) => $b['up_to'] ?? PHP_FLOAT_MAX)
            ->values()
            ->all();
    }

    private function percentageCharge(ShippingMethod $method, float $subtotal): float
    {
        $charge = $subtotal * $method->config('percent') / 100;
        $min = $method->config('min');
        $max = $method->config('max');

        $charge = max($charge, $min);

        return $max > 0 ? min($charge, $max) : $charge;
    }

    /**
     * Total shipped weight in kg. With $volumetric, each product counts at
     * max(actual, L×W×H ÷ divisor) — bulky-but-light items (wheelchairs,
     * pillows) are priced by the space they take.
     *
     * @param  list<array{product: ?Product, quantity: float, is_gift: bool}>  $lines
     */
    private function weight(array $lines, bool $volumetric, float $divisor): float
    {
        return (float) collect($lines)->sum(function ($line) use ($volumetric, $divisor) {
            $product = $line['product'];

            if (! $product) {
                return 0.0;
            }

            $unit = (float) ($product->weight ?? 0);

            if ($volumetric && $product->length && $product->width && $product->height) {
                $unit = max($unit, ($product->length * $product->width * $product->height) / $divisor);
            }

            return $unit * $line['quantity'];
        });
    }

    private function divisor(ShippingMethod $method): float
    {
        $divisor = $method->config('divisor', self::DEFAULT_VOLUMETRIC_DIVISOR);

        return $divisor > 0 ? $divisor : self::DEFAULT_VOLUMETRIC_DIVISOR;
    }
}
