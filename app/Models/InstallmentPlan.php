<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'master_profile_id', 'role', 'reference', 'frequency', 'count', 'amount', 'total',
        'start_date', 'custom_offsets', 'support_rule', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'total'          => 'decimal:2',
            'start_date'     => 'date',
            'custom_offsets' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('no');
    }

    /** "Monthly 2/6", "Custom 15/30/45" */
    public function badge(?int $paid = null): string
    {
        if ($this->frequency === 'custom' && $this->custom_offsets) {
            return 'Custom ' . implode('/', $this->custom_offsets);
        }

        $paid ??= $this->installments->where('status', 'paid')->count();

        return ucfirst($this->frequency) . " {$paid}/{$this->count}";
    }
}
