<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'status',
        'plan_name',
        'plan_features',
        'registered_domain',
        'registered_company',
        'activated_at',
        'expires_at',
        'last_checked_at',
        'last_response',
    ];

    protected $casts = [
        'license_key' => 'encrypted',
        'plan_features' => 'array',
        'last_response' => 'array',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
