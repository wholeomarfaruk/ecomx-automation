<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per web request — written by App\Http\Middleware\LogRequest,
 * shown in admin → Visits Url (App\Livewire\Admin\Visits\Visits).
 */
class RequestLog extends Model
{
    use MassPrunable;

    const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Daily via model:prune (routes/console.php). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('request-logs.retention_days')));
    }

    /** 2xx/3xx/4xx/5xx bucket used for colouring and filtering. */
    public function getStatusClassAttribute(): string
    {
        return intdiv($this->status_code, 100) . 'xx';
    }

    public function getIsSlowAttribute(): bool
    {
        return $this->duration_ms >= config('request-logs.slow_ms');
    }
}
