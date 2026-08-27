<?php

namespace App\Models;

use App\Models\Marketing\MarketingEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Device extends Model
{
    protected $fillable = [
        'customer_id', 'user_id', 'fingerprint', 'user_agent', 'sec_ch_ua',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(DeviceIpAddress::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(DeviceVisit::class);
    }

    public function marketingEvents(): HasMany
    {
        return $this->hasMany(MarketingEvent::class);
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(Block::class, 'blockable');
    }

    public function activeBlocks()
    {
        return $this->blocks()->applicable();
    }

    public function hasActiveBlock(?string $scope = null): bool
    {
        return $this->activeBlocks()->when($scope, fn ($q) => $q->forScope($scope))->exists();
    }
}
