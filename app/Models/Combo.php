<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    protected $fillable = [
        'customer_id', 'device_id', 'name', 'quantity', 'price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price'    => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComboItem::class);
    }

    public function recalculatePrice(): void
    {
        $this->price = $this->items()->sum('price');
        $this->save();
    }
}
