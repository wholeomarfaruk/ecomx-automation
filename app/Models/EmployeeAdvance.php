<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends Model
{
    protected $fillable = ['master_profile_id', 'amount', 'monthly_deduction', 'balance', 'given_on', 'journal_entry_id'];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
            'balance'           => 'decimal:2',
            'given_on'          => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }
}
