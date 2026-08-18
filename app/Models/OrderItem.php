<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'combo_id', 'is_gift',
        'product_name', 'variant_name', 'sku',
        'quantity', 'unit_price', 'purchase_price',
        'discount_amount', 'tax_amount', 'total_amount',
        'returned_quantity',
    ];

    protected function casts(): array
    {
        return [
            'is_gift'           => 'boolean',
            'quantity'          => 'decimal:3',
            'unit_price'        => 'decimal:4',
            'purchase_price'    => 'decimal:4',
            'discount_amount'   => 'decimal:2',
            'tax_amount'        => 'decimal:2',
            'total_amount'      => 'decimal:2',
            'returned_quantity' => 'decimal:3',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
