<?php

namespace App\Livewire\EcomxFashion;

use App\Marketing\Events\AddToCart as AddToCartEvent;
use App\Marketing\Services\MarketingEventService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Device;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * ecomx-fashion's own copy of App\Livewire\Website\CartManager — kept
 * separate so the legacy theme's cart is never touched. Adds
 * product_variant_id support (size/color) on top of the original, which
 * the legacy cart never tracked. Renders its own drawer view directly
 * (livewire.ecomx-fashion.cart-manager, reusing the theme's existing
 * .cart-drawer/.cart-item CSS classes — same pattern as WishlistDrawer),
 * driven by wire:click — no Alpine store cart array.
 */
class CartManager extends Component
{
    public $cart;

    public function mount()
    {
        $this->cart = $this->getCart();
        $this->cart->load('items.product', 'items.variant.values.productAttributeValue.attributeValue.attribute', 'items.variant.media');
        $this->refreshBadge();
    }

    public function getCart()
    {
        $device = request()->attributes->get('device');

        return Cart::firstOrCreate(
            [
                'customer_id' => null,
                'device_id' => $device?->id,
            ],
            []
        );
    }

    #[On('add-to-cart')]
    public function addToCart($productId, $variantId = null, $qty = 1)
    {
        $product = Product::find($productId);

        if (! $product) {
            $this->dispatch('notify', type: 'error', message: 'Product not found.');
            return;
        }

        if ($product->stock_status === 'out_of_stock') {
            $this->dispatch('notify', type: 'error', message: 'This product is out of stock.');
            return;
        }

        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)->where('product_id', $productId)->first();

            if (! $variant) {
                $this->dispatch('notify', type: 'error', message: 'Selected variant is not available.');
                return;
            }

            if ($variant->status !== 'active' || $variant->stock_quantity <= 0) {
                $this->dispatch('notify', type: 'error', message: 'This variant is out of stock.');
                return;
            }
        } else {
            // No variant explicitly chosen (e.g. product/flash-sale card
            // "Add to Cart" has no size/color picker) — fall back to the
            // first in-stock variant (by display order) so items with real
            // variants are never added to the cart variant-less.
            $variant = ProductVariant::where('product_id', $productId)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->orderBy('sort_order')
                ->first();
        }

        $variantId = $variant?->id;

        $qty = max(1, (int) $qty);
        $cart = $this->getCart();
        $stockLimit = $variant ? $variant->stock_quantity : null;
        $price = $variant ? ($variant->sale_price ?? $variant->price) : ($product->sale_price ?? $product->price);

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($item) {
            if ($stockLimit !== null && $item->quantity + $qty > $stockLimit) {
                $this->dispatch('notify', type: 'error', message: 'No more stock available for this product.');
                return;
            }
            $item->quantity += $qty;
            $item->save();
        } else {
            if ($stockLimit !== null && $qty > $stockLimit) {
                $this->dispatch('notify', type: 'error', message: 'No more stock available for this product.');
                return;
            }
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $qty,
                'price' => $price,
            ]);
        }

        $this->updateCartTotals($cart);
        $this->refreshBadgeAndOpenDrawer();

        $this->recordAddToCart($product, $item);

        $this->dispatch('notify', type: 'success', message: 'Added to cart successfully.');
    }

    private function recordAddToCart(Product $product, CartItem $item): void
    {
        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $event = AddToCartEvent::create(
            contentId: $item->product_id,
            contentName: $product->name,
            quantity: $item->quantity,
            value: (float) ($item->quantity * $item->price),
            currency: 'BDT',
        );

        $result = app(MarketingEventService::class)->recordForCurrentRequest(
            event: $event,
            device: $device,
            customer: auth()->check() ? auth()->user()->customer : null,
        );

        // Mid-request Livewire action (AJAX, not a full page load) — the
        // pending-events blade component only fires on a fresh render, so
        // the payload is pushed via a browser event instead (see
        // Livewire.on('marketing-event', ...) in app.js).
        $this->dispatch('marketing-event', payload: $result['browserPayload']);
    }

    #[On('cart-clear')]
    public function clearCart()
    {
        $this->cart->items()->delete();
        $this->updateCartTotals($this->cart);
        $this->refreshBadge();
    }

    #[On('cart-remove-item')]
    public function removeItem($rowId)
    {
        $cartItem = CartItem::find($rowId);

        if (! $cartItem) {
            $this->dispatch('notify', type: 'error', message: 'Cart item not found.');
            return;
        }

        $cartItem->delete();
        $this->updateCartTotals($this->cart);
        $this->refreshBadge();
    }

    #[On('cart-increase-qty')]
    public function increaseQty($itemId)
    {
        $item = CartItem::findOrFail($itemId);

        $stockLimit = $item->variant_id
            ? $item->variant?->stock_quantity
            : null;

        if ($stockLimit !== null && $item->quantity + 1 > $stockLimit) {
            $this->dispatch('notify', type: 'error', message: 'No more stock available for this product.');
            return;
        }

        $item->quantity += 1;
        $item->save();

        $this->updateCartTotals($item->cart);
        $this->refreshBadge();
    }

    #[On('cart-decrease-qty')]
    public function decreaseQty($itemId)
    {
        $item = CartItem::findOrFail($itemId);

        if ($item->quantity > 1) {
            $item->quantity -= 1;
            $item->save();
        } else {
            $item->delete();
        }

        $this->updateCartTotals($item->cart ?? $this->cart);
        $this->refreshBadge();
    }

    /**
     * Applies a variant and/or quantity change from the pencil-icon "edit
     * item" modal (App\Livewire\EcomxFashion\EditCartItemVariant). If the
     * chosen variant matches another existing line for the same product
     * (e.g. user switches back to a variant already in the cart), merges
     * into that line instead of leaving two rows for the same variant —
     * same dedup rule addToCart() uses.
     */
    #[On('cart-update-item')]
    public function updateItem($cartItemId, $variantId, $qty)
    {
        $item = CartItem::find($cartItemId);

        if (! $item) {
            $this->dispatch('notify', type: 'error', message: 'Cart item not found.');
            return;
        }

        $product = $item->product;
        if (! $product) {
            $this->dispatch('notify', type: 'error', message: 'Product not found.');
            return;
        }

        $variant = null;
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)->where('product_id', $item->product_id)->first();

            if (! $variant) {
                $this->dispatch('notify', type: 'error', message: 'Selected variant is not available.');
                return;
            }

            if ($variant->status !== 'active' || $variant->stock_quantity <= 0) {
                $this->dispatch('notify', type: 'error', message: 'This variant is out of stock.');
                return;
            }
        }

        $qty = max(1, (int) $qty);
        $stockLimit = $variant ? $variant->stock_quantity : null;

        if ($stockLimit !== null && $qty > $stockLimit) {
            $this->dispatch('notify', type: 'error', message: 'No more stock available for this product.');
            return;
        }

        $price = $variant ? ($variant->sale_price ?? $variant->price) : ($product->sale_price ?? $product->price);

        $duplicate = CartItem::where('cart_id', $item->cart_id)
            ->where('product_id', $item->product_id)
            ->where('variant_id', $variantId)
            ->where('id', '!=', $item->id)
            ->first();

        if ($duplicate) {
            $duplicate->quantity += $qty;
            $duplicate->save();
            $item->delete();
        } else {
            $item->variant_id = $variantId;
            $item->quantity = $qty;
            $item->price = $price;
            $item->save();
        }

        $this->updateCartTotals($item->cart ?? $this->cart);
        $this->refreshBadge();
        $this->dispatch('notify', type: 'success', message: 'Cart item updated.');
    }

    private function updateCartTotals($cart)
    {
        $cart->load('items.product', 'items.variant.values.productAttributeValue.attributeValue.attribute');

        $subtotal = $cart->items->sum(fn ($item) => $item->quantity * $item->price);

        $cart->update(['subtotal' => $subtotal]);

        $this->cart = $cart->fresh('items.product', 'items.variant.values.productAttributeValue.attributeValue.attribute');
    }

    /**
     * Refreshes the header badge count only — used by every mutation that
     * happens while the drawer may already be open (remove/increase/decrease/
     * clear). This component re-renders its own line items on every
     * wire:click already; this event is solely for the header badge, which
     * lives outside this component's DOM subtree.
     */
    private function refreshBadge(): void
    {
        $itemCount = (int) $this->cart->items->sum('quantity');
        setcookie('_cart_count', $itemCount, time() + (86400 * 30), '/');

        $this->dispatch('cart-updated', cartcount: $itemCount);
    }

    /**
     * Same badge refresh, plus opens the drawer — only ever called from
     * addToCart(), which is the one mutation meant to slide the drawer in.
     */
    private function refreshBadgeAndOpenDrawer(): void
    {
        $itemCount = (int) $this->cart->items->sum('quantity');
        setcookie('_cart_count', $itemCount, time() + (86400 * 30), '/');

        $this->dispatch('cart-updated', cartcount: $itemCount);
        $this->dispatch('cart-open');
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.cart-manager');
    }
}
