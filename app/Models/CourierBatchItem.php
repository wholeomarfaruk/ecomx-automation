<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One editable row on a Bulk Courier sheet (CourierBatch). */
class CourierBatchItem extends Model
{
    public const DRAFT = 'draft';
    public const BOOKED = 'booked';
    public const FAILED = 'failed';

    protected $fillable = [
        'courier_batch_id', 'order_id', 'courier_id', 'recipient_name', 'recipient_phone',
        'recipient_address', 'cod_amount', 'weight', 'quantity', 'description', 'instruction',
        'status', 'tracking_number', 'error_message', 'sort_order', 'booked_at',
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'weight' => 'decimal:3',
        'quantity' => 'integer',
        'booked_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CourierBatch::class, 'courier_batch_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /** Sheet order. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isBooked(): bool
    {
        return $this->status === self::BOOKED;
    }
}
