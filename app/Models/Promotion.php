<?php

namespace App\Models;

use App\Enums\Sales\PromotionStatus;
use App\Enums\Sales\PromotionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Promotion extends Model
{
    protected $fillable = [
        'type', 'name', 'description', 'status', 'priority',
        'starts_at', 'ends_at', 'stackable',
    ];

    protected function casts(): array
    {
        return [
            'type'       => PromotionType::class,
            'status'     => PromotionStatus::class,
            'stackable'  => 'boolean',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
        ];
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(Coupon::class);
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PromotionItem::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PromotionCondition::class);
    }

    public function discountRules(): HasMany
    {
        return $this->hasMany(PromotionDiscountRule::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PromotionStatus::ACTIVE)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
