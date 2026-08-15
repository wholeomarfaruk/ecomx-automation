<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    protected $fillable = [
        'event_key',
        'label',
        'channel_email',
        'channel_sms',
        'channel_push',
        'channel_browser',
        'channel_database',
        'email_template_key',
        'sms_template_key',
    ];

    protected $casts = [
        'channel_email' => 'boolean',
        'channel_sms' => 'boolean',
        'channel_push' => 'boolean',
        'channel_browser' => 'boolean',
        'channel_database' => 'boolean',
    ];

    public function enabledChannels(): array
    {
        return array_keys(array_filter([
            'email' => $this->channel_email,
            'sms' => $this->channel_sms,
            'push' => $this->channel_push,
            'browser' => $this->channel_browser,
            'database' => $this->channel_database,
        ]));
    }
}
