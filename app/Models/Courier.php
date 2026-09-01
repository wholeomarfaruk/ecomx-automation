<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'driver_key',
        'logo',
        'description',
        'type',
        'capabilities',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CourierAccount::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class);
    }

    public function activeAccount(): ?CourierAccount
    {
        return $this->accounts()->where('is_active', true)->orderByDesc('is_default')->first();
    }

    public function hasCapability(string $capability): bool
    {
        return (bool) ($this->capabilities[$capability] ?? false);
    }
}
