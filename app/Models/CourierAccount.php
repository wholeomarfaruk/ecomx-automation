<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierAccount extends Model
{
    protected $fillable = [
        'courier_id',
        'name',
        'credentials',
        'settings',
        'is_default',
        'is_active',
        'last_tested_at',
        'last_balance_check_at',
        'last_balance',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_balance_check_at' => 'datetime',
            'last_balance' => 'decimal:4',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class);
    }

    public static function forCourier(int $courierId, string $name = 'Default Account'): self
    {
        return static::query()->firstOrCreate(
            ['courier_id' => $courierId, 'name' => $name],
        );
    }
}
