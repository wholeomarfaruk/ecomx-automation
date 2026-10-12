<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionTier extends Model
{
    protected $fillable = ['commission_scheme_id', 'from_amount', 'to_amount', 'rate'];

    protected function casts(): array
    {
        return ['from_amount' => 'decimal:2', 'to_amount' => 'decimal:2', 'rate' => 'decimal:3'];
    }
}
