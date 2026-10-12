<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'master_profile_id', 'code', 'name', 'company_name', 'email', 'phone', 'alternative_phone',
        'address', 'balance', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Keep the Master Profiles role list in sync wherever a supplier is
        // created or re-linked (Purchase module, Accounts Panel).
        static::saved(function (Supplier $supplier) {
            if ($supplier->wasRecentlyCreated || $supplier->wasChanged('master_profile_id')) {
                MasterProfileRole::ensure($supplier->master_profile_id, 'supplier');
            }
        });
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
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
