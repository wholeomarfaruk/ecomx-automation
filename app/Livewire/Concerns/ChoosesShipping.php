<?php

namespace App\Livewire\Concerns;

use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Shipping\ShippingQuote;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Delivery-area / delivery-method state for the storefront checkouts, priced
 * by ShippingCalculator from the zones managed under Sales → Shipping.
 *
 * $delivery_area holds the zone *code* (dhaka, outside, …) — it's what the
 * Offer module's "Shipping Method" condition matches, and what the address
 * form uses to pick Dhaka as the city. The method picker only shows when
 * the chosen zone has more than one active method.
 */
trait ChoosesShipping
{
    public string $delivery_area = '';

    public ?int $shipping_method_id = null;

    /** @return Collection<int, ShippingZone> */
    #[Computed]
    public function shippingZones(): Collection
    {
        return app(ShippingCalculator::class)->zones();
    }

    protected function initShipping(): void
    {
        $zone = $this->selectedShippingZone() ?? $this->shippingZones->first();

        $this->delivery_area = $zone?->code ?? '';
        $this->shipping_method_id = $zone?->methods->first()?->id;
    }

    public function updatedDeliveryArea(): void
    {
        $this->shipping_method_id = $this->selectedShippingZone()?->methods->first()?->id;
    }

    protected function selectedShippingZone(): ?ShippingZone
    {
        return $this->shippingZones->firstWhere('code', $this->delivery_area);
    }

    protected function selectedShippingMethod(): ?ShippingMethod
    {
        $methods = $this->selectedShippingZone()?->methods;

        return $methods?->firstWhere('id', $this->shipping_method_id) ?? $methods?->first();
    }

    protected function shippingQuote(Cart $cart, ?ShippingMethod $method = null): ?ShippingQuote
    {
        $method ??= $this->selectedShippingMethod();

        if (! $method) {
            return null;
        }

        $calculator = app(ShippingCalculator::class);

        return $calculator->quote($method, $calculator->cartLines($cart), (float) $cart->subtotal);
    }

    /**
     * Each zone's charge for this cart with its first method — the price
     * shown next to the area name in the dropdown.
     *
     * @return array<string, float>
     */
    protected function zoneCharges(Cart $cart): array
    {
        return $this->shippingZones
            ->mapWithKeys(fn (ShippingZone $zone) => [
                $zone->code => $this->shippingQuote($cart, $zone->methods->first())?->amount ?? 0.0,
            ])
            ->all();
    }

    /**
     * Each method in the chosen zone with its charge for this cart, for the
     * method picker (only rendered when there's more than one).
     *
     * @return list<array{id: int, name: string, delivery_time: ?string, charge: float}>
     */
    protected function methodOptions(Cart $cart): array
    {
        return ($this->selectedShippingZone()?->methods ?? collect())
            ->map(fn (ShippingMethod $method) => [
                'id'            => $method->id,
                'name'          => $method->name,
                'delivery_time' => $method->delivery_time,
                'charge'        => $this->shippingQuote($cart, $method)?->amount ?? 0.0,
            ])
            ->values()
            ->all();
    }

    /**
     * Only the area is validated: shippingZones() never returns a zone
     * without an active method, and selectedShippingMethod() falls back to
     * the zone's first method when the picked one was disabled meanwhile —
     * the picker is hidden for single-method zones, so an error on
     * shipping_method_id could have nowhere to show.
     */
    protected function shippingRules(): array
    {
        return [
            'delivery_area' => ['required', Rule::in($this->shippingZones->pluck('code')->all())],
        ];
    }
}
