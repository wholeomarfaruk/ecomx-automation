<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per AI Order extraction (Sales → Bulk Order → AI Order) — what
 * came in, whether the parser resolved it alone or the AI fallback ran, and
 * the AI call's model/tokens/cost/latency. Also the extraction cache (same
 * input_hash → reuse ai_result) and the link to the orders placed from it.
 * See App\OrderIntake\OrderIntakeService.
 */
class OrderIntakeLog extends Model
{
    public const RESOLUTION_PARSER = 'parser';
    public const RESOLUTION_AI = 'ai';
    public const RESOLUTION_AI_FAILED = 'ai_failed';
    public const RESOLUTION_AI_SKIPPED = 'ai_skipped';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ai_result' => 'array',
            'order_ids' => 'array',
            'cost'      => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usedAi(): bool
    {
        return in_array($this->resolution, [self::RESOLUTION_AI, self::RESOLUTION_AI_FAILED], true);
    }
}
