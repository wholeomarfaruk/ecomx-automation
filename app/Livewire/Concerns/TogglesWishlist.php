<?php

namespace App\Livewire\Concerns;

use App\Models\Device;
use App\Models\Wishlist;
use App\Models\WishlistItem;

/**
 * Adds a `toggleWishlist` Livewire action to any ecomx-fashion page/component
 * whose product cards render a heart button bound to `wire:click`. Persists
 * to the wishlists/wishlist_items tables, keyed on the visitor's Device (see
 * DeviceTracker middleware) — no customer login required.
 */
trait TogglesWishlist
{
    public function toggleWishlist(int $productId, ?int $productVariantId = null): void
    {
        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            $this->dispatch('notify', message: 'Unable to update wishlist right now.', type: 'error');

            return;
        }

        $wishlist = Wishlist::query()->firstOrCreate(['device_id' => $device->id]);

        $existing = $wishlist->items()
            ->where('product_id', $productId)
            ->where('variant_id', $productVariantId)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Removed from wishlist.';
        } else {
            $wishlist->items()->create([
                'product_id' => $productId,
                'variant_id' => $productVariantId,
            ]);
            $message = 'Added to wishlist.';
        }

        $this->dispatch('notify', message: $message);
        $this->dispatch('wishlist-updated', count: WishlistItem::forDevice($device)->count());
    }
}
