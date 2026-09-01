<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierApiLog extends Model
{
    protected $fillable = [
        'courier_id',
        'courier_account_id',
        'courier_shipment_id',
        'action',
        'method',
        'endpoint',
        'http_status',
        'request_payload',
        'response_payload',
        'success',
        'error_code',
        'error_message',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function courierAccount(): BelongsTo
    {
        return $this->belongsTo(CourierAccount::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(CourierShipment::class, 'courier_shipment_id');
    }
}
