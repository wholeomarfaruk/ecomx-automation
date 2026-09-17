<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'cod_fee_rate',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
            'cod_fee_rate' => 'decimal:2',
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

    /**
     * The "Courier Cash" ledger account (under 1045) tracking COD money
     * this courier is holding but hasn't remitted yet — seeded once per
     * courier by AccountSeeder::seedCourierCashAccounts(). Not to be
     * confused with CourierAccount, which is API login credentials.
     */
    public function cashAccount(): HasOne
    {
        return $this->hasOne(Account::class);
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

    /**
     * Pathao doesn't accept a ?secret= query string back — it echoes the
     * secret you enter on its own dashboard verbatim in the
     * X-PATHAO-Signature header on every call instead, so its registered
     * URL must be the bare endpoint (see CourierWebhookController::handle).
     */
    public function webhookUrl(): string
    {
        $url = url('/api/webhooks/courier/' . $this->slug);

        if (! $this->webhook_secret || $this->driver_key === 'pathao') {
            return $url;
        }

        return "{$url}?secret={$this->webhook_secret}";
    }
}
