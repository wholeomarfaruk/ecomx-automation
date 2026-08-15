<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushGatewayConfig extends Model
{
    protected $fillable = [
        'driver_key',
        'credentials',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public static function forDriver(string $driverKey): self
    {
        return static::query()->firstOrCreate(['driver_key' => $driverKey]);
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
