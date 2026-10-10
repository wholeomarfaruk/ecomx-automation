<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One FraudShield result per phone — see App\Services\FraudShield\FraudShield. */
class FraudCheck extends Model
{
    protected $fillable = [
        'phone', 'score', 'level', 'label', 'total_parcel', 'success_parcel',
        'cancelled_parcel', 'success_ratio', 'review_count', 'payload', 'checked_by', 'checked_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'score' => 'integer',
        'total_parcel' => 'integer',
        'success_parcel' => 'integer',
        'cancelled_parcel' => 'integer',
        'success_ratio' => 'float',
        'review_count' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /** safe / warning / danger / neutral (no parcel history) — drives colours. */
    public function tone(): string
    {
        $level = strtolower((string) $this->level);

        if (in_array($level, ['high', 'danger', 'risky', 'fraud', 'blacklisted'], true)) {
            return 'danger';
        }

        if ($this->total_parcel === 0) {
            return 'neutral';
        }

        return match (true) {
            in_array($level, ['medium', 'moderate', 'warning', 'caution'], true) => 'warning',
            in_array($level, ['safe', 'low', 'trusted'], true) => 'safe',
            $this->score !== null && $this->score >= 60 => 'danger',
            $this->score !== null && $this->score >= 30 => 'warning',
            $this->success_ratio < 60 => 'danger',
            $this->success_ratio < 80 => 'warning',
            default => 'safe',
        };
    }

    public function badgeClass(): string
    {
        return match ($this->tone()) {
            'safe' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'danger' => 'bg-red-50 text-red-700 ring-red-200',
            default => 'bg-gray-50 text-gray-500 ring-gray-200',
        };
    }

    public function barClass(): string
    {
        return match ($this->tone()) {
            'safe' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            'danger' => 'bg-red-500',
            default => 'bg-gray-300',
        };
    }

    /** Short label for the badge ("Safe", "High", "No history"). */
    public function levelLabel(): string
    {
        if ($this->tone() === 'neutral') {
            return 'No history';
        }

        return $this->level ? ucfirst(str_replace('_', ' ', $this->level)) : ucfirst($this->tone());
    }

    /** @return array<string, array> per-courier rows (summary excluded). */
    public function couriers(): array
    {
        $data = $this->payload['courierData'] ?? [];

        return collect(is_array($data) ? $data : [])
            ->except('summary')
            ->filter(fn ($row) => is_array($row))
            ->all();
    }

    /** @return list<array> */
    public function reviews(): array
    {
        $reviews = $this->payload['reviews'] ?? [];

        return is_array($reviews) ? array_values($reviews) : [];
    }
}
