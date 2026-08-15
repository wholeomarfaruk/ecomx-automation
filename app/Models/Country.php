<?php

namespace App\Models;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name', 'local_name', 'code', 'phone_code', 'currency_code',
        'emoji_flag', 'is_register_allowed', 'is_active', 'sort_order', 'timezone',
    ];

    protected function casts(): array
    {
        return [
            'is_register_allowed' => 'boolean',
            'is_active'           => 'boolean',
        ];
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Countries available for public registration.
     * When the 'restrict_by_country' setting is ON, returns only is_register_allowed rows.
     * When OFF, returns all active countries.
     */
    public function scopeAllowedForRegistration(Builder $query): Builder
    {
        $restricted = Setting::get('restrict_by_country', false, 'registration');

        $query->where('is_active', true);

        if ($restricted) {
            $query->where('is_register_allowed', true);
        }

        return $query->orderBy('sort_order')->orderBy('name');
    }
}
