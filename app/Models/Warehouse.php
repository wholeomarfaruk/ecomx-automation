<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'branch_id', 'code', 'name', 'status', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class);
    }

    public static function default(): self
    {
        return static::firstWhere('is_default', true)
            ?? static::firstOrCreate(
                ['code' => 'MAIN'],
                ['name' => 'Main Warehouse', 'status' => 'active', 'is_default' => true]
            );
    }
}
