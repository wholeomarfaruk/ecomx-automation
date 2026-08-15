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

    /**
     * Signed effect of an invoice type on the supplier's balance.
     * purchase/return increase what is owed to the supplier; advance/payment reduce it.
     */
    public static function balanceDelta(string $type, float $amount): float
    {
        return match ($type) {
            'purchase', 'return' => $amount,
            'advance', 'payment' => -$amount,
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
