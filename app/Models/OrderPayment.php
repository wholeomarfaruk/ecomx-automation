<?php

namespace App\Models;

use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'payment_method', 'transaction_id', 'amount', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status'         => PaymentStatus::class,
            'amount'         => 'decimal:2',
            'paid_at'        => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
