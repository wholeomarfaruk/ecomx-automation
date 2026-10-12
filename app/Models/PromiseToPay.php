<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** kind: promise (will pay amount by date) | callback (call again on date). */
class PromiseToPay extends Model
{
    protected $table = 'promises_to_pay';

    protected $fillable = ['master_profile_id', 'kind', 'due_date', 'amount', 'note', 'status', 'balance_at_promise', 'created_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'amount' => 'decimal:2', 'balance_at_promise' => 'decimal:2', 'resolved_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
