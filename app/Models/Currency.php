<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'symbol', 'decimal_places'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('code');
    }
}
