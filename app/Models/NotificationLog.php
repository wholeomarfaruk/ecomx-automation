<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'event_key',
        'channel',
        'recipient',
        'status',
        'provider',
        'error_message',
        'provider_response',
        'retry_count',
        'sent_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at' => 'datetime',
    ];
}
