<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGatewayConfig extends Model
{
    protected $fillable = [
        'driver_key',
        'credentials',
        'sender_id',
        'default_country_code',
        'timeout',
        'retry_attempts',
        'rate_limit_per_minute',
        'is_active',
        'last_tested_at',
        'last_balance_check_at',
        'last_balance',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_balance_check_at' => 'datetime',
        'last_balance' => 'decimal:4',
    ];

    protected $attributes = [
        'timeout' => 30,
        'retry_attempts' => 2,
    ];

    public static function forDriver(string $driverKey): self
    {
        return static::query()->firstOrCreate(['driver_key' => $driverKey], [
            'timeout' => 30,
            'retry_attempts' => 2,
        ]);
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
