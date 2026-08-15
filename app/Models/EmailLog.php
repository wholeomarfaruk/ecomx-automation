<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'mailer',
        'to',
        'subject',
        'status',
        'error_message',
        'raw_response',
        'sent_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'sent_at' => 'datetime',
    ];
}
