<?php

namespace App\Models;

use App\Enums\Sales\CouponCustomerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponCustomer extends Model
{
    protected $fillable = [
        'coupon_id', 'customer_id', 'status', 'saved_at', 'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'      => CouponCustomerStatus::class,
            'saved_at'    => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
