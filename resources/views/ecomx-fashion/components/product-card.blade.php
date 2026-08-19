@props(['product'])
@php
    $productUrl = $product['url'] ?? route('ecomx-fashion.product', $product['slug'] ?? '');
    $productId = $product['id'] ?? null;
    $isWished = $product['is_wished'] ?? false;
@endphp
<div class="pcard" x-data="{ added:false }">
    <a href="{{ $productUrl }}" class="pcard__media">
        <x-ux-img :id="$product['img']" :w="700" :alt="$product['name']" class="pcard__img" />
        @if(!empty($product['tag']))<span class="pcard__tag">{{ $product['tag'] }}</span>@endif
        @if ($productId)
            <button type="button" class="pcard__wish {{ $isWished ? 'is-on' : '' }}" wire:click.prevent="toggleWishlist({{ $productId }})" wire:loading.attr="disabled" wire:target="toggleWishlist({{ $productId }})" aria-label="Add to wishlist"><x-icon name="heart" /></button>
        @else
            <button type="button" class="pcard__wish" disabled aria-label="Add to wishlist"><x-icon name="heart" /></button>
        @endif
    </a>
    <div class="pcard__body">
        <div class="pcard__row">
            <a href="{{ $productUrl }}" class="pcard__name">{{ $product['name'] }}</a>
            <span class="price">৳{{ number_format($product['price']) }}</span>
        </div>
        <div class="pcard__row">
            <span class="muted" style="font-size:12px">{{ $product['cat'] }}</span>
            <div class="pcard__swatches">
                @foreach($product['colors'] as $col)<span class="pcard__swatch" style="background:{{ $col }}"></span>@endforeach
            </div>
        </div>
        @if ($productId)
            <button type="button" class="pcard__add" :class="added && 'is-added'" @click="added=true; $dispatch('add-to-cart', { productId: {{ $productId }} }); setTimeout(()=>added=false,1600)" x-text="added ? 'Added ✓' : 'Add to Cart'"></button>
        @else
            <button type="button" class="pcard__add" disabled>Add to Cart</button>
        @endif
    </div>
</div>
