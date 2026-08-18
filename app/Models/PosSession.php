<?php

namespace App\Models;

use App\Enums\Sales\PosSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    protected $fillable = [
        'register_id', 'user_id', 'status',
        'opening_cash', 'closing_cash',
        'opened_at', 'closed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'       => PosSessionStatus::class,
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'opened_at'    => 'datetime',
            'closed_at'    => 'datetime',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'register_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'session_id');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'session_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PosSessionStatus::OPEN);
    }

    /**
     * Expected cash in the drawer right now: opening float plus every
     * inflow/outflow recorded since, excluding the closing entry itself.
     */
    public function expectedCash(): float
    {
        $movements = $this->cashMovements()
            ->whereNotIn('type', ['opening', 'closing'])
            ->get();

        $net = $movements->sum(fn (PosCashMovement $m) => $m->type->isInflow() ? (float) $m->amount : -(float) $m->amount);

        return (float) $this->opening_cash + $net;
    }
}
