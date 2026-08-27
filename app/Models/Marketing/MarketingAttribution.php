<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAttribution extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_touch_at' => 'datetime',
            'last_touch_at' => 'datetime',

            'attribution_data' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }
}
