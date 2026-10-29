<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'variant_id', 'combo_id', 'exchange_id', 'is_gift',
        'product_name', 'variant_name', 'sku',
        'quantity', 'unit_price', 'purchase_price',
        'discount_amount', 'tax_amount', 'total_amount',
        'returned_quantity', 'return_posted_quantity', 'lost_quantity', 'lost_posted_quantity', 'delivered_quantity', 'stock_deducted',
    ];

    protected function casts(): array
    {
        return [
            'is_gift'            => 'boolean',
            'quantity'           => 'decimal:3',
            'unit_price'         => 'decimal:4',
            'purchase_price'     => 'decimal:4',
            'discount_amount'    => 'decimal:2',
            'tax_amount'         => 'decimal:2',
            'total_amount'       => 'decimal:2',
            'returned_quantity'  => 'decimal:3',
            // How much of returned_quantity PostOrderReturn has already reversed in the books.
            'return_posted_quantity' => 'decimal:3',
            // Part of returned_quantity the courier lost — not owed by the customer, never restocked.
            'lost_quantity'          => 'decimal:3',
            // How much of lost_quantity is written off in the books (Stock Loss).
            'lost_posted_quantity'   => 'decimal:3',
            'delivered_quantity' => 'decimal:3',
            'stock_deducted'     => 'decimal:3',
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

    /** The exchange that added this line (its new goods), if any. */
    public function exchange(): BelongsTo
    {
        return $this->belongsTo(OrderExchange::class, 'exchange_id');
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    /**
     * Which inventory batch(es) actually fulfilled this item at packing
     * time — an item's quantity can span several batches at different
     * costs. Sum of quantity across these rows should equal
     * delivered_quantity once packed.
     */
    public function batchAllocations(): HasMany
    {
        return $this->hasMany(OrderItemBatch::class);
    }
}
