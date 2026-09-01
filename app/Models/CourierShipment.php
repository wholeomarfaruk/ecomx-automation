<?php

namespace App\Models;

use App\Enums\Sales\CourierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierShipment extends Model
{
    protected $fillable = [
        'order_id',
        'courier_id',
        'courier_account_id',
        'shipment_id',
        'tracking_number',
        'consignment_id',
        'status',
        'previous_status',
        'cod_amount',
        'delivery_charge',
        'return_charge',
        'pickup_requested_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'cod_amount' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'return_charge' => 'decimal:2',
            'pickup_requested_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function courierAccount(): BelongsTo
    {
        return $this->belongsTo(CourierAccount::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(CourierTrackingEvent::class)->orderBy('event_at');
    }

    public function statusEnum(): CourierStatus
    {
        return CourierStatus::tryFrom($this->status) ?? CourierStatus::PENDING;
    }
}
