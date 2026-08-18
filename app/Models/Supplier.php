<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'company_name', 'email', 'phone', 'alternative_phone',
        'address', 'balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
