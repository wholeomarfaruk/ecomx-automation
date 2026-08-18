<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'promotion_id', 'code',
        'usage_limit', 'usage_limit_per_customer',
        'min_order_amount', 'max_discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'min_order_amount'    => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(CouponCustomer::class);
    }
}
