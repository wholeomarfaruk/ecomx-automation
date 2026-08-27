{{-- Loved Items — persisted wishlist grouped by when each item was saved --}}
<div x-data
    x-show="$store.ui.wishlistOpen" x-cloak class="overlay" style="z-index:210"
    @click.self="$store.ui.wishlistOpen=false" @keydown.escape.window="$store.ui.wishlistOpen=false">
    <div class="cart-drawer">
        <div class="cart-drawer__head">
            <span class="cart-drawer__title">Loved Items<span class="cart-drawer__count">({{ $this->items->count() }})</span></span>
            <button class="modal__close" @click="$store.ui.wishlistOpen=false" aria-label="Close">✕</button>
        </div>

        @if ($this->items->isEmpty())
            <div class="cart-drawer__empty">
                <x-icon name="heart" />
                <p>Nothing loved yet.</p>
                <button class="btn btn--outline btn--pill" @click="$store.ui.wishlistOpen=false">Continue browsing</button>
            </div>
        @endif

        <div class="cart-drawer__body">
            @foreach ($groups as $group)
                <div class="wishlist-group">
                    <p class="wishlist-group__label">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        @php
                            $product = $item->product;
                            $options = $item->variant?->options_map ?? [];
                        @endphp
                        <div class="wish-item" wire:key="wish-item-{{ $item->id }}">
                            <a href="{{ $product?->url ?? '#' }}" class="cart-item__media">
                                <x-ux-img :id="$item->display_image" :w="200" :alt="$product?->name ?? ''" />
                            </a>
                            <div class="cart-item__body">
                                <div class="cart-item__row">
                                    <a href="{{ $product?->url ?? '#' }}" class="cart-item__name">{{ $product?->name ?? 'Deleted product' }}</a>
                                    <span>
                                        @if ($product?->discount_price)
                                            <span class="cart-item__price--old">৳{{ number_format($product->price) }}</span>
                                        @endif
                                        <span class="cart-item__price">৳{{ number_format($product?->discounted_price ?? 0) }}</span>
                                    </span>
                                </div>
                                @if (! empty($options))
                                    <span class="cart-item__meta">{{ implode(' · ', $options) }}</span>
                                @endif
                                <span class="wish-item__time">Loved {{ $item->created_at?->diffForHumans() }}</span>
                                <div class="cart-item__foot">
                                    <button type="button" class="wish-item__move" wire:click="moveToCart({{ $item->id }})">Add to Cart</button>
                                    <button type="button" class="cart-item__remove" wire:click="remove({{ $item->id }})">Remove</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
