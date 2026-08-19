{{-- Real DB-backed cart drawer — same .cart-drawer/.cart-item CSS the theme
     already ships (see resources/ecomx-fashion/scss/components/_cart-drawer.scss),
     driven entirely by wire:click. Open/close is the only thing left to Alpine.

     This root `.overlay` div IS the Livewire component root (carries wire:id),
     so every wire:click inside (remove/qty/clear) makes Livewire morph this
     exact element — re-adding x-cloak / resetting Alpine's client-applied
     display style as part of reconciling it against the freshly-rendered
     server HTML, which cycles visibility and replays any plain CSS
     `animation` on descendants (the shared .cart-drawer rule) even though
     cartOpen never changed. Alpine's own x-transition is morph-aware (Alpine
     preserves element identity and only re-fires enter/leave when the
     x-show expression's *value* actually flips), so the slide-in lives here
     via x-transition instead — .cart-drawer's plain `animation` is
     neutralized below since this replaces it. --}}
<div x-data
    x-show="$store.ui.cartOpen" x-cloak class="overlay" style="z-index:210"
    x-transition:enter="cart-drawer-slide-enter" x-transition:enter-start="cart-drawer-slide-enter-start" x-transition:enter-end="cart-drawer-slide-enter-end"
    @click.self="$store.ui.cartOpen=false" @keydown.escape.window="$store.ui.cartOpen=false">
    <div class="cart-drawer" style="animation:none">
        @php
            $cartRegularTotal = $cart->items->sum(function ($item) {
                $comparePrice = $item->variant ? $item->variant->price : $item->product?->price;

                return ((float) $comparePrice) * $item->quantity;
            });
            $cartDiscountedTotal = (float) $cart->subtotal;
            $cartSavings = max(0, $cartRegularTotal - $cartDiscountedTotal);
        @endphp
        <div class="cart-drawer__head">
            <span class="cart-drawer__title">Your Cart<span class="cart-drawer__count">({{ $cart->items->sum('quantity') }})</span></span>
            <button class="modal__close" @click="$store.ui.cartOpen=false" aria-label="Close cart">✕</button>
        </div>

        @if ($cart->items->isEmpty())
            <div class="cart-drawer__empty">
                <x-icon name="cart" />
                <p>Your cart is empty.</p>
                <button class="btn btn--outline btn--pill" @click="$store.ui.cartOpen=false">Continue shopping</button>
            </div>
        @endif

        <div class="cart-drawer__body">
            @foreach ($cart->items as $item)
                @php
                    $product = $item->product;
                    $variant = $item->variant;
                    $options = $variant?->options_map ?? [];
                    $comparePrice = $variant ? $variant->price : $product?->price;
                    $hasSale = ((float) $comparePrice) > (float) $item->price;
                    $productHasVariants = $product && $product->variants()->where('is_active', true)->exists();
                @endphp
                <div class="cart-item" wire:key="cart-item-{{ $item->id }}">
                    <div class="cart-item__media">
                        <x-ux-img :id="$product?->featured_image" :w="200" :alt="$product?->name ?? ''" />
                    </div>
                    <div class="cart-item__body">
                        <div class="cart-item__row">
                            <span class="cart-item__name">{{ $product?->name ?? 'Deleted product' }}</span>
                            <span>
                                @if ($hasSale)
                                    <span class="cart-item__price--old">৳{{ number_format($comparePrice) }}</span>
                                @endif
                                <span class="cart-item__price">৳{{ number_format($item->price) }}</span>
                            </span>
                        </div>
                        @if (! empty($options) || $productHasVariants)
                            <span class="cart-item__meta">
                                {{ implode(' · ', $options) }}
                                @if ($productHasVariants)
                                    <livewire:ecomx-fashion.edit-cart-item-variant :cart-item="$item" :key="'edit-'.$item->id" />
                                @endif
                            </span>
                        @endif
                        <div class="cart-item__foot">
                            <div class="qty">
                                <button type="button" wire:click="decreaseQty({{ $item->id }})" aria-label="Decrease quantity">−</button>
                                <span>{{ $item->quantity }}</span>
                                <button type="button" wire:click="increaseQty({{ $item->id }})" aria-label="Increase quantity">+</button>
                            </div>
                            <button type="button" class="cart-item__remove" wire:click="removeItem({{ $item->id }})">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($cart->items->isNotEmpty())
            <div class="cart-drawer__foot">
                <div class="cart-drawer__subtotal" style="position:relative">
                    <span>Subtotal</span>
                    <span style="position:relative" x-data="{ open:false }" @click.outside="open=false">
                        @if ($cartSavings > 0)
                            <button type="button" class="cart-savings-float__badge" @click="open=!open">💰 Saving ৳{{ number_format($cartSavings) }}</button>
                            <div class="cart-savings-float__tip" x-show="open" x-cloak x-transition:enter.duration.150ms>
                                <div class="cart-savings__row"><span>🏷️ Regular price</span><span>৳{{ number_format($cartRegularTotal) }}</span></div>
                                <div class="cart-savings__row"><span>💳 Discount price</span><span>৳{{ number_format($cartDiscountedTotal) }}</span></div>
                                <div class="cart-savings__row cart-savings__row--total"><span>✨ Your savings</span><span>৳{{ number_format($cartSavings) }}</span></div>
                            </div>
                        @endif
                        ৳{{ number_format($cart->subtotal) }}
                    </span>
                </div>
                <p class="cart-drawer__note">Shipping and taxes calculated at checkout.</p>
                <a href="{{ route('ecomx-fashion.checkout') }}" class="btn btn--primary btn--block cart-checkout-btn"><x-icon name="cart" /> Checkout <x-icon name="arrow-right" /></a>
                <button type="button" class="btn btn--outline btn--block" @click="$store.ui.cartOpen=false"><x-icon name="arrow-left" /> Continue shopping</button>
            </div>
        @endif
    </div>
</div>
