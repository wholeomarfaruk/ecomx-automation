<?php

namespace App\Models;

use App\Models\Concerns\HasDisplayImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasDisplayImage;

    protected $fillable = [
        'wishlist_id', 'product_id', 'variant_id',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Scopes to wishlist items belonging to the given device — pass the
     * Device the DeviceTracker middleware already resolved for this request
     * (request()->attributes->get('device')), not a cookie value.
     */
    public function scopeForDevice(Builder $query, Device|int|null $device): Builder
    {
        if (! $device) {
            return $query->whereRaw('1 = 0');
        }

        $deviceId = $device instanceof Device ? $device->id : $device;

        return $query->whereHas('wishlist', fn ($q) => $q->where('device_id', $deviceId));
    }
}
