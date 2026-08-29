<?php

namespace App\Livewire\Concerns;

use App\Models\Device;
use App\Models\Product;
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

        // Callers that don't have a variant picker (product-card grids) only
        // ever pass the product id. For a variable product that still needs
        // to resolve to a real variant row — fall back to its first active
        // variant (in-stock ones first) so the wishlist always references a
        // purchasable line item, not a dangling "the product in general".
        $productVariantId ??= $this->defaultVariantIdFor($productId);

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

    /**
     * Simple/combo products have no variant rows — null is correct for them.
     * Variable products must resolve to a real variant, so the wishlist item
     * always points at a purchasable line: prefer the first in-stock active
     * variant (by sort_order), falling back to the first active one if all
     * are out of stock.
     */
    protected function defaultVariantIdFor(int $productId): ?int
    {
        $product = Product::query()->find($productId);

        if (! $product || $product->product_type?->value !== 'variable') {
            return null;
        }

        $variants = $product->variants()->active()->get();

        return $variants->firstWhere(fn ($v) => $v->stock_quantity > 0)?->id
            ?? $variants->first()?->id;
    }
}
