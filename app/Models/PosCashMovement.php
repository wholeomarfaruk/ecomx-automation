<?php

namespace App\Models;

use App\Enums\Sales\PosCashMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosCashMovement extends Model
{
    protected $fillable = [
        'session_id', 'type', 'amount', 'reason', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type'   => PosCashMovementType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
