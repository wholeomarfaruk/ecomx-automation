<?php

namespace App\Livewire\Concerns;

use App\Models\Device;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;

/**
 * Adds wishlist Livewire actions to any ecomx-fashion page/component whose
 * product cards render a heart button bound to `wire:click`. Persists to the
 * wishlists/wishlist_items tables, keyed on the visitor's Device (see
 * DeviceTracker middleware) — no customer login required.
 */
trait TogglesWishlist
{
    /**
     * Blind toggle: add if absent, remove if present. Used where the button
     * flips its own state from a fresh server render each time (buy box),
     * so there's no optimistic client state that could disagree with it.
     */
    public function toggleWishlist(int $productId, ?int $productVariantId = null): void
    {
        $device = $this->wishlistDevice();

        if (! $device) {
            return;
        }

        $productVariantId ??= $this->defaultVariantIdFor($productId);

        $wishlist = Wishlist::query()->firstOrCreate(['device_id' => $device->id]);

        $existing = $wishlist->items()
            ->where('product_id', $productId)
            ->where('variant_id', $productVariantId)
            ->first();

        $this->applyWishlistChange($wishlist, $device, $productId, $productVariantId, wished: ! $existing);
    }

    /**
     * Explicit set: makes the DB match $wished exactly, regardless of how
     * many times this fired. Used by product-card grids, whose heart button
     * flips an Alpine-local boolean instantly on click (optimistic UI) and
     * sends this debounced — so a burst of clicks collapses into one call
     * carrying only the final settled state, instead of replaying every
     * click as a blind toggle (which would drift from the visible icon).
     */
    public function setWishlist(int $productId, bool $wished, ?int $productVariantId = null): void
    {
        $device = $this->wishlistDevice();

        if (! $device) {
            return;
        }

        $productVariantId ??= $this->defaultVariantIdFor($productId);

        $wishlist = Wishlist::query()->firstOrCreate(['device_id' => $device->id]);

        $this->applyWishlistChange($wishlist, $device, $productId, $productVariantId, $wished);
    }

    protected function wishlistDevice(): ?Device
    {
        $device = request()->attributes->get('device');

        if (! $device) {
            $this->dispatch('notify', message: 'Unable to update wishlist right now.', type: 'error');
        }

        return $device;
    }

    protected function applyWishlistChange(Wishlist $wishlist, Device $device, int $productId, ?int $variantId, bool $wished): void
    {
        $existing = $wishlist->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($wished && ! $existing) {
            $wishlist->items()->create([
                'product_id' => $productId,
                'variant_id' => $variantId,
            ]);
            $message = 'Added to wishlist.';
        } elseif (! $wished && $existing) {
            $existing->delete();
            $message = 'Removed from wishlist.';
        } else {
            // Already in the desired state (e.g. a debounced call landed
            // after the state had already changed some other way) — nothing
            // to persist, but the header count may still be stale for this
            // component instance, so keep it in sync below.
            $message = null;
        }

        if ($message) {
            $this->dispatch('notify', message: $message);
        }

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
