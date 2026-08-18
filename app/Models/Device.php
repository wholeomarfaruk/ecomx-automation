<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'customer_id', 'fingerprint', 'user_agent',
        'device_type', 'platform',
        'device_brand', 'device_model', 'manufacturer',
        'operating_system', 'os_version',
        'browser', 'browser_version',
        'app_version', 'build_number',
        'screen_resolution', 'screen_density',
        'language', 'timezone',
        'fcm_token', 'ip_address',
        'last_login_at', 'last_active_at',
        'is_trusted', 'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at'  => 'datetime',
            'last_active_at' => 'datetime',
            'is_trusted'     => 'boolean',
            'is_allowed'     => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
