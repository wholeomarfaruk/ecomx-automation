<?php

namespace App\Models\Marketing;

use App\Models\Customer;
use App\Models\Device;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketingEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',

            'value' => 'decimal:4',

            'identity_data' => 'array',
            'device_data' => 'array',
            'page_data' => 'array',
            'commerce_data' => 'array',
            'custom_data' => 'array',
            'context_data' => 'array',
            'metadata' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MarketingSession::class, 'session_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketingEventItem::class, 'marketing_event_id');
    }

    public function attribution(): HasOne
    {
        return $this->hasOne(MarketingAttribution::class, 'marketing_event_id');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(MarketingEventDestination::class, 'marketing_event_id');
    }
}
