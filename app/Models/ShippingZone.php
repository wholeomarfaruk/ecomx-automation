<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = ['name', 'code', 'is_active', 'is_default', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('sort_order')->orderBy('id');
    }
}
