<?php

namespace App\Models\Marketing;

use App\Marketing\Enums\MarketingDeliveryStatus;
use App\Marketing\Enums\MarketingDestination as MarketingDestinationEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingEventDestination extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'destination' => MarketingDestinationEnum::class,
            'status' => MarketingDeliveryStatus::class,

            'first_attempted_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'next_retry_at' => 'datetime',

            'response_data' => 'array',
            'metadata' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }
}
