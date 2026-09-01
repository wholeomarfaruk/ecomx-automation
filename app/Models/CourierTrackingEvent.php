<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierTrackingEvent extends Model
{
    protected $fillable = [
        'courier_shipment_id',
        'status',
        'raw_status',
        'message',
        'location',
        'event_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(CourierShipment::class, 'courier_shipment_id');
    }
}
