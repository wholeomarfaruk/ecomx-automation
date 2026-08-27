<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class MarketingIdentityRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
