<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayClosing extends Model
{
    protected $fillable = ['date', 'version', 'status', 'snapshot', 'pdf_path', 'note', 'short_amount', 'closed_by', 'closed_at', 'reopened_by', 'reopened_at'];

    protected function casts(): array
    {
        return ['date' => 'date', 'snapshot' => 'array', 'short_amount' => 'decimal:2', 'closed_at' => 'datetime', 'reopened_at' => 'datetime'];
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** The latest version for a date. */
    public static function latestFor(string $date): ?self
    {
        return static::whereDate('date', $date)->orderByDesc('version')->first();
    }
}
