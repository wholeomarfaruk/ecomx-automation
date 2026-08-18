<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAddress extends Model
{
    protected $fillable = [
        'customer_id', 'address_type',
        'name', 'phone', 'alternative_phone',
        'country_id', 'state_id', 'city_id',
        'ps_id', 'area_id', 'zip_code_id', 'street_id', 'house_id',
        'full_address', 'delivery_note',
        'latitude', 'longitude',
        'is_default_billing', 'is_default_shipping', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default_billing'  => 'boolean',
            'is_default_shipping' => 'boolean',
            'is_active'           => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
