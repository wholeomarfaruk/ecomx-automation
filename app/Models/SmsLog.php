<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'driver_key',
        'to',
        'message',
        'status',
        'message_id',
        'cost',
        'error_code',
        'error_message',
        'provider_response',
        'raw_response',
        'request_payload',
        'context',
        'retry_count',
        'sent_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'raw_response' => 'array',
        'request_payload' => 'array',
        'cost' => 'decimal:4',
        'sent_at' => 'datetime',
    ];
}
