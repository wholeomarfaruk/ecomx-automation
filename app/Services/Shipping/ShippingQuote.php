<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;

/** What one shipping method charges for one cart. */
final class ShippingQuote
{
    public function __construct(
        public readonly float $amount,
        public readonly float $baseAmount,
        public readonly bool $freeByThreshold,
        public readonly ?float $freeOver,
        public readonly float $remainingForFree,
        public readonly float $weight,
        public readonly int $quantity,
    ) {}

    /**
     * Snapshot stored on orders.shipping_meta, next to shipping_zone_id /
     * shipping_method_id, so later zone/method/rate edits don't rewrite
     * what a past order was charged.
     */
    public function toMeta(ShippingMethod $method): array
    {
        return [
            'zone'              => $method->zone?->name,
            'zone_code'         => $method->zone?->code,
            'method'            => $method->name,
            'delivery_time'     => $method->delivery_time,
            'rate_type'         => $method->rate_type->value,
            'rate_config'       => $method->rate_config,
            'base_amount'       => $this->baseAmount,
            'free_over'         => $this->freeOver,
            'free_by_threshold' => $this->freeByThreshold,
            'weight'            => $this->weight,
            'quantity'          => $this->quantity,
            'amount'            => $this->amount,
        ];
    }
}
