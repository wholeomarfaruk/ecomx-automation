<?php

namespace App\Models;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'customer_id', 'source',
        'status', 'payment_status', 'fulfillment_status',
        'currency',
        'subtotal', 'discount_amount', 'shipping_amount', 'shipping_discount', 'tax_amount',
        'total_amount', 'paid_amount', 'due_amount',
        'customer_note', 'admin_note',
        'billing_address_id', 'shipping_address_id', 'coupon_id', 'coupon_code',
        'placed_at', 'confirmed_at', 'completed_at', 'cancelled_at',
        'courier_provider', 'courier_tracking_number', 'courier_charge',
        'courier_status', 'courier_meta', 'courier_status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'source'             => OrderSource::class,
            'status'             => OrderStatus::class,
            'payment_status'     => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'courier_status'     => CourierStatus::class,
            'courier_meta'       => 'array',
            'subtotal'           => 'decimal:2',
            'discount_amount'    => 'decimal:2',
            'shipping_amount'    => 'decimal:2',
            'shipping_discount'  => 'decimal:2',
            'tax_amount'         => 'decimal:2',
            'total_amount'       => 'decimal:2',
            'paid_amount'        => 'decimal:2',
            'due_amount'         => 'decimal:2',
            'courier_charge'     => 'decimal:2',
            'placed_at'                => 'datetime',
            'confirmed_at'              => 'datetime',
            'completed_at'              => 'datetime',
            'cancelled_at'              => 'datetime',
            'courier_status_updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'billing_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'shipping_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function courierShipments(): HasMany
    {
        return $this->hasMany(CourierShipment::class)->latest();
    }

    public function posSale(): HasOne
    {
        return $this->hasOne(PosSale::class);
    }

    public function recalculateTotals(): void
    {
        $itemsTotal = $this->items()->get()->sum(fn (OrderItem $item) => $item->is_gift ? 0 : (float) $item->total_amount);

        // shipping_discount can never exceed shipping_amount or go negative,
        // regardless of how it was set (e.g. a stale value from before the
        // shipping amount was edited down).
        $shippingDiscount = max(0.0, min((float) $this->shipping_amount, (float) $this->shipping_discount));
        $netShipping       = (float) $this->shipping_amount - $shippingDiscount;

        $this->subtotal          = $itemsTotal;
        $this->shipping_discount = $shippingDiscount;
        $this->total_amount      = $itemsTotal - $this->discount_amount + $netShipping + $this->tax_amount;
        $this->paid_amount       = $this->payments()->where('status', PaymentStatus::PAID)->sum('amount');
        $this->due_amount        = max(0, $this->total_amount - $this->paid_amount);
        $this->save();
    }

    public function syncReturnStatus(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $anyReturned = $items->contains(fn (OrderItem $item) => (float) $item->returned_quantity > 0);
        $allReturned = $items->every(fn (OrderItem $item) => (float) $item->returned_quantity >= (float) $item->quantity);

        if ($allReturned) {
            $this->status = OrderStatus::RETURNED;
        } elseif ($anyReturned) {
            $this->status = OrderStatus::PARTIALLY_RETURNED;
        }

        $this->save();
    }
}
