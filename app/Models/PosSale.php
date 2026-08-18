<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSale extends Model
{
    protected $fillable = [
        'session_id', 'order_id', 'customer_id', 'created_by',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
