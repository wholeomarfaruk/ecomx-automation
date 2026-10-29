<?php

namespace App\Models;

use App\Enums\Sales\ExchangeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An exchange inside an order: returnItems are the delivered units the
 * customer sends back, newItems the order lines it adds. See
 * App\Actions\Sales\Exchanges\* for every change to one.
 */
class OrderExchange extends Model
{
    protected $fillable = [
        'order_id', 'number', 'status', 'reason', 'note', 'charge_amount',
        'order_charge_id', 'courier_shipment_id', 'created_by',
        'completed_at', 'failed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status'        => ExchangeStatus::class,
            'charge_amount' => 'decimal:2',
            'completed_at'  => 'datetime',
            'failed_at'     => 'datetime',
            'cancelled_at'  => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Delivered units coming back. */
    public function returnItems(): HasMany
    {
        return $this->hasMany(OrderExchangeItem::class);
    }

    /** Order lines this exchange added (the new goods). */
    public function newItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'exchange_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(CourierShipment::class, 'courier_shipment_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(OrderCharge::class, 'order_charge_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** "268-EX1" — the reference sent to the courier for this exchange's parcel. */
    public function reference(): string
    {
        return "{$this->order_id}-EX{$this->number}";
    }

    /** What the customer gets back for the returned units — their price after their share of the order discount. */
    public function creditAmount(?Order $order = null): float
    {
        $order ??= $this->order;
        $factor = $order->returnDiscountFactor();

        return round($this->returnItems->sum(function (OrderExchangeItem $line) use ($factor) {
            $item = $line->orderItem;
            $qty = (float) $item?->quantity;

            return $item && $qty > 0 && ! $item->is_gift ? (float) $item->total_amount / $qty * (float) $line->quantity * $factor : 0.0;
        }), 2);
    }

    /** Price of the new goods. */
    public function newAmount(): float
    {
        return round((float) $this->newItems->sum(fn (OrderItem $i) => $i->is_gift ? 0 : (float) $i->total_amount), 2);
    }

    /** New goods + exchange charge − credit: > 0 the customer pays it, < 0 the shop owes it. */
    public function balance(?Order $order = null): float
    {
        return round($this->newAmount() + (float) $this->charge_amount - $this->creditAmount($order), 2);
    }
}
