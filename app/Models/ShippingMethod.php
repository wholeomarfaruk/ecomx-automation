<?php

namespace App\Models;

use App\Enums\Sales\ShippingRateType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    protected $fillable = [
        'shipping_zone_id', 'name', 'delivery_time',
        'rate_type', 'rate_config', 'free_over',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate_type'   => ShippingRateType::class,
            'rate_config' => 'array',
            'free_over'   => 'decimal:2',
            'is_active'   => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /** Short admin-facing description of the rate, e.g. "৳60 first 1 kg + ৳20/kg". */
    public function rateSummary(): string
    {
        $tk = fn (string $key, float $default = 0.0) => '৳' . number_format($this->config($key, $default));
        $bands = count($this->rate_config['bands'] ?? []);

        $summary = match ($this->rate_type) {
            ShippingRateType::FLAT           => $tk('amount'),
            ShippingRateType::WEIGHT         => $tk('base_charge') . ' first ' . (float) $this->config('base_weight', 1.0) . ' kg + ' . $tk('per_kg') . '/kg',
            ShippingRateType::WEIGHT_BANDS   => $bands . ' weight band' . ($bands === 1 ? '' : 's'),
            ShippingRateType::QUANTITY       => $tk('first_charge') . ' first item + ' . $tk('additional_charge') . ' each extra',
            ShippingRateType::SUBTOTAL_BANDS => $bands . ' cart amount band' . ($bands === 1 ? '' : 's'),
            ShippingRateType::PERCENTAGE     => (float) $this->config('percent') . '% of cart',
            ShippingRateType::FREE           => 'Free',
        };

        if ($this->rate_type->usesWeight() && ! empty($this->rate_config['volumetric'])) {
            $summary .= ' · volumetric';
        }

        return $summary;
    }

    /** One rate_config value as a float, 0 when missing or blank. */
    public function config(string $key, float $default = 0.0): float
    {
        $value = $this->rate_config[$key] ?? null;

        return is_numeric($value) ? (float) $value : $default;
    }
}
