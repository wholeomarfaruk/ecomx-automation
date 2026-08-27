<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingReportExport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(MarketingSavedReport::class, 'marketing_saved_report_id');
    }
}
