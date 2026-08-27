<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    protected $guarded = [];

    public function source(): BelongsTo
    {
        return $this->belongsTo(MarketingSource::class, 'marketing_source_id');
    }

    /**
     * There is no campaign_id FK on marketing_events — events are recorded
     * (and may accumulate) before a campaign is ever registered here, so
     * this relation joins on the raw utm_campaign string instead
     * (campaign_key is expected to match marketing_events.utm_campaign).
     */
    public function events(): HasMany
    {
        return $this->hasMany(MarketingEvent::class, 'utm_campaign', 'campaign_key');
    }
}
