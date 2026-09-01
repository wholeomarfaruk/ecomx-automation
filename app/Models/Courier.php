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
        'webhook_secret',
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

    /** Generates (or rotates) the token appended to this courier's webhook URL — see the add_webhook_secret migration for why this app owns it, not the courier. */
    public function generateWebhookSecret(): string
    {
        $secret = bin2hex(random_bytes(24));
        $this->update(['webhook_secret' => $secret]);

        return $secret;
    }

    public function webhookUrl(): string
    {
        $url = url('/api/webhooks/courier/' . $this->slug);

        return $this->webhook_secret ? "{$url}?secret={$this->webhook_secret}" : $url;
    }
}
