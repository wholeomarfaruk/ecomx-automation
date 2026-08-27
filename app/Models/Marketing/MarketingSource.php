<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSource extends Model
{
    protected $guarded = [];

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class);
    }
}
