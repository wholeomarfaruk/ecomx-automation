<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportArchive extends Model
{
    protected $fillable = ['saved_report_id', 'report_key', 'kind', 'title', 'period', 'path'];

    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class);
    }
}
