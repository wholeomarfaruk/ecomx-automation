<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'customer_code',
        'first_name', 'last_name', 'full_name',
        'email', 'phone', 'alternative_phone',
        'gender', 'date_of_birth',
        'customer_group_id',
        'reward_points', 'wallet_balance',
        'email_verified_at', 'phone_verified_at',
        'status', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth'      => 'date',
            'reward_points'      => 'decimal:2',
            'wallet_balance'     => 'decimal:2',
            'email_verified_at'  => 'datetime',
            'phone_verified_at'  => 'datetime',
            'metadata'           => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(Combo::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    public function couponCustomers(): HasMany
    {
        return $this->hasMany(CouponCustomer::class);
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }
}
