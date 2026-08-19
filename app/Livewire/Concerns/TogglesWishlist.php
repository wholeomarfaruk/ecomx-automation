<?php

namespace App\Livewire\Concerns;

/**
 * Adds a `toggleWishlist` Livewire action to any ecomx-fashion page/component
 * whose product cards render a heart button bound to `wire:click`. The
 * ecomx-fashion theme runs on demo data (see App\Support\EcomxFashion\Catalog)
 * with no wishlist persistence layer wired up, so this only drives the
 * client-side toast/badge — nothing is stored server-side.
 */
trait TogglesWishlist
{
    public function toggleWishlist(int $productId, ?int $productVariantId = null): void
    {
        $this->dispatch('notify', message: 'Wishlist is a demo action in this theme — nothing is persisted.');
    }
}
