<?php

namespace App\Models;

use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'type', 'payment_method', 'cash_account_id', 'transaction_id', 'amount', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type'            => OrderPaymentType::class,
            'payment_method'  => PaymentMethod::class,
            'status'          => PaymentStatus::class,
            'amount'          => 'decimal:2',
            'paid_at'         => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }
}
