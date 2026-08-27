<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class WishlistDrawer extends Component
{
    use TogglesWishlist;

    public function getItemsProperty(): Collection
    {
        $device = request()->attributes->get('device');

        if (! $device) {
            return collect();
        }

        return WishlistItem::query()
            ->forDevice($device)
            ->with(['product', 'variant.values.productAttributeValue.attributeValue.attribute', 'variant.media'])
            ->latest()
            ->get();
    }

    public function getGroupsProperty(): array
    {
        $labels = ['Today', 'This Week', 'This Month', 'Earlier'];
        $grouped = $this->items->groupBy(function (WishlistItem $item) {
            $days = $item->created_at?->startOfDay()->diffInDays(now()->startOfDay()) ?? 999;

            return match (true) {
                $days === 0 => 'Today',
                $days <= 6 => 'This Week',
                $days <= 30 => 'This Month',
                default => 'Earlier',
            };
        });

        return collect($labels)
            ->filter(fn ($label) => $grouped->has($label))
            ->map(fn ($label) => ['label' => $label, 'items' => $grouped->get($label)])
            ->values()
            ->all();
    }

    #[On('wishlist-updated')]
    public function refreshDrawer(): void
    {
        // No-op body — @[On] just needs to trigger a re-render so
        // getItemsProperty()/getGroupsProperty() recompute against the
        // latest wishlist_items rows after any page's toggleWishlist() call.
    }

    public function remove(int $wishlistItemId): void
    {
        $device = request()->attributes->get('device');

        WishlistItem::query()
            ->where('id', $wishlistItemId)
            ->forDevice($device)
            ->delete();

        $this->dispatch('wishlist-updated', count: $this->items->count());
    }

    public function moveToCart(int $wishlistItemId): void
    {
        // Cart persistence for the ecomx-fashion theme is not wired to the
        // backend yet (see resources/ecomx-fashion/js/app.js — cartItems is
        // still client-side demo state), so this only removes the item from
        // the wishlist; the item still needs to be added to the cart client-side.
        $this->remove($wishlistItemId);
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.wishlist-drawer', [
            'groups' => $this->groups,
        ]);
    }
}
