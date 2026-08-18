<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name', 'description',
        'discount_type', 'discount_value',
        'minimum_order_amount', 'minimum_order_qty',
        'allow_credit', 'reward_points_enabled',
        'is_default', 'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'discount_value'        => 'decimal:2',
            'minimum_order_amount'  => 'decimal:2',
            'minimum_order_qty'     => 'integer',
            'allow_credit'          => 'boolean',
            'reward_points_enabled' => 'boolean',
            'is_default'            => 'boolean',
            'is_active'             => 'boolean',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
