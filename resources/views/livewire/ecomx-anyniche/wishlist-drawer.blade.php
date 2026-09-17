{{-- Loved Items — persisted wishlist grouped by when each item was saved.
     Same .jtc-scrim/.jtc-cart structure as the cart drawer (see
     cart-manager.blade.php) so both offcanvas panels look and behave
     identically. --}}
<div x-data>
    <div class="jtc-scrim" :class="$store.ui.wishlistOpen && 'is-open'" x-show="$store.ui.wishlistOpen" x-cloak @click="$store.ui.wishlistOpen=false"></div>

    <aside class="jtc-cart" :class="$store.ui.wishlistOpen && 'is-open'" aria-label="Loved items" @keydown.escape.window="$store.ui.wishlistOpen=false">
        <div class="jtc-cart__head">
            <h3>Loved items <span class="jtc-cart__count">({{ $this->items->count() }})</span></h3>
            <button class="jtc-cart__close" aria-label="Close" @click="$store.ui.wishlistOpen=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
        </div>

        <div class="jtc-cart__body">
            @if ($this->items->isEmpty())
                <div class="jtc-cart__empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" width="56" height="56"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
                    <p>Nothing loved yet.</p>
                    <p>Tap the heart on any product to save it here.</p>
                </div>
            @endif

            @foreach ($groups as $group)
                <p style="font-size:0.76rem;font-weight:600;color:#6b7a73;text-transform:uppercase;letter-spacing:.03em;margin:16px 0 8px">{{ $group['label'] }}</p>
                @foreach ($group['items'] as $item)
                    @php
                        $product = $item->product;
                        $variant = $item->variant;
                        $options = $variant?->options_map ?? [];
                        $comparePrice = $variant ? $variant->price : $product?->price;
                        $salePrice = $variant ? ($variant->sale_price ?? $variant->price) : ($product?->sale_price ?? $product?->price);
                        $hasSale = $comparePrice !== null && $salePrice !== null && (float) $salePrice < (float) $comparePrice;
                    @endphp
                    <div class="jtc-cart__line" wire:key="wish-item-{{ $item->id }}">
                        <a href="{{ $product?->url ?? '#' }}">
                            <img class="jtc-cart__thumb" src="{{ $item->display_image ? file_path($item->display_image) : '' }}" alt="{{ $product?->name ?? '' }}">
                        </a>
                        <div>
                            <a href="{{ $product?->url ?? '#' }}" class="jtc-cart__name" style="color:inherit;text-decoration:none">{{ $product?->name ?? 'Deleted product' }}</a>
                            <div class="jtc-cart__meta">
                                @if ($hasSale)
                                    <span style="text-decoration:line-through;opacity:.6">৳{{ number_format($comparePrice) }}</span>
                                @endif
                                ৳{{ number_format($salePrice ?? 0) }}
                                @if (! empty($options))
                                    · {{ implode(' · ', $options) }}
                                @endif
                            </div>
                            <div class="jtc-cart__meta">Loved {{ $item->created_at?->diffForHumans() }}</div>
                        </div>
                        <div class="jtc-cart__lineright">
                            <button type="button" class="jtc-btn jtc-btn--primary jtc-form__channel-toggle" wire:click="moveToCart({{ $item->id }})">Add to cart</button>
                            <button type="button" class="jtc-cart__remove" wire:click="remove({{ $item->id }})">Remove</button>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </aside>
</div>
