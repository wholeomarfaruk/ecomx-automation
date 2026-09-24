{{-- Real DB-backed cart drawer — driven entirely by wire:click, styled with
     the theme's real .jtc-scrim/.jtc-cart classes (see
     resources/ecomx-anyniche/scss/storefront/_modals.scss).

     This root div IS the Livewire component root (carries wire:id), so
     every wire:click inside (remove/qty/clear) makes Livewire morph this
     exact element. Alpine's x-show/x-cloak on $store.ui.cartOpen is
     morph-aware (Alpine preserves element identity and only re-fires when
     the expression's value actually flips), so open/close animation stays
     correct across re-renders. --}}
<div x-data>
    <div class="jtc-scrim" :class="$store.ui.cartOpen && 'is-open'" x-show="$store.ui.cartOpen" x-cloak @click="$store.ui.cartOpen=false"></div>

    <aside class="jtc-cart" :class="$store.ui.cartOpen && 'is-open'" aria-label="Shopping cart" @keydown.escape.window="$store.ui.cartOpen=false">
        @php
            $cartRegularTotal = $cart->items->sum(function ($item) {
                $comparePrice = $item->variant ? $item->variant->price : $item->product?->price;

                return ((float) $comparePrice) * $item->quantity;
            });
            $cartDiscountedTotal = (float) $cart->subtotal;
            $cartSavings = max(0, $cartRegularTotal - $cartDiscountedTotal);
        @endphp
        <div class="jtc-cart__head">
            <h3>Your cart <span class="jtc-cart__count">({{ $cart->items->sum('quantity') }})</span></h3>
            <button class="jtc-cart__close" aria-label="Close cart" @click="$store.ui.cartOpen=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
        </div>

        <div class="jtc-cart__body">
            @if ($cart->items->isEmpty())
                <div class="jtc-cart__empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" width="56" height="56"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2.5 3h2.2l2.1 12.1a1.8 1.8 0 0 0 1.8 1.5h9.1a1.8 1.8 0 0 0 1.8-1.4l1.6-7.2H6"></path></svg>
                    <p>Your cart is empty.</p>
                    <p>Add something to get started.</p>
                </div>
            @endif

            @foreach ($cart->items as $item)
                @php
                    $product = $item->product;
                    $variant = $item->variant;
                    $options = $variant?->options_map ?? [];
                    $comparePrice = $variant ? $variant->price : $product?->price;
                    $hasSale = ((float) $comparePrice) > (float) $item->price;
                    $productHasVariants = $product && $product->variants()->where('status', 'active')->exists();
                @endphp
                <div class="jtc-cart__line" wire:key="cart-item-{{ $item->id }}">
                    <x-anyniche::ux-img class="jtc-cart__thumb" :id="$item->display_image" :w="200" :alt="$product?->name ?? ''" />
                    <div>
                        <div class="jtc-cart__name">{{ $product?->name ?? 'Deleted product' }}</div>
                        <div class="jtc-cart__meta">
                            @if ($hasSale)
                                <span style="text-decoration:line-through;opacity:.6">৳{{ number_format($comparePrice) }}</span>
                            @endif
                            ৳{{ number_format($item->price) }}
                            @if (! empty($options) || $productHasVariants)
                                · {{ implode(' · ', $options) }}
                                @if ($productHasVariants)
                                    <livewire:ecomx-anyniche.edit-cart-item-variant :cart-item="$item" :key="'edit-'.$item->id" />
                                @endif
                            @endif
                        </div>
                        <div class="jtc-cart__qty">
                            <button type="button" wire:click="decreaseQty({{ $item->id }})" aria-label="Decrease quantity">−</button>
                            <span>{{ $item->quantity }}</span>
                            <button type="button" wire:click="increaseQty({{ $item->id }})" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                    <div class="jtc-cart__lineright">
                        <span class="jtc-cart__linetotal">৳{{ number_format($item->price * $item->quantity) }}</span>
                        <button type="button" class="jtc-cart__remove" wire:click="removeItem({{ $item->id }})">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($cart->items->isNotEmpty())
            <div class="jtc-cart__foot">
                <div class="jtc-cart__row">
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
                <div class="jtc-cart__row"><span>Delivery</span><span>Calculated at checkout</span></div>
                @foreach ($offers['applied'] as $applied)
                    <div class="jtc-cart__row" wire:key="cart-offer-{{ $applied['promotion_id'] }}"><span>🎁 {{ $applied['name'] }}</span><span>−৳{{ number_format($applied['discount']) }}</span></div>
                @endforeach
                <div class="jtc-cart__total"><span>Total</span><span>৳{{ number_format(max(0, (float) $cart->subtotal - $offers['discount'])) }}</span></div>
                <a href="{{ route('ecomx-anyniche.checkout') }}" class="jtc-btn jtc-btn--primary jtc-btn--block" style="padding:14px">Proceed to checkout</a>
                <p class="jtc-cart__note">Taxes &amp; delivery calculated at checkout</p>
            </div>
        @endif
    </aside>
</div>
