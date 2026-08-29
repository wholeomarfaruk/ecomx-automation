@props(['product'])
@php
    $productUrl = $product['url'] ?? route('ecomx-fashion.product', $product['slug'] ?? '');
    $productId = $product['id'] ?? null;
    $isWished = $product['is_wished'] ?? false;
    $hasSale = !empty($product['sale']);
@endphp
<div class="pcard" x-data="{ added:false, wished: @js($isWished) }">
    <a href="{{ $productUrl }}" class="pcard__media">
        <x-ux-img :id="$product['img']" :w="700" :alt="$product['name']" class="pcard__img" />
        @if(!empty($product['tag']))<span class="pcard__tag">{{ $product['tag'] }}</span>@endif
        @if ($productId)
            <button type="button" class="pcard__wish" :class="wished && 'is-on'" @click.prevent="wished = !wished; $wire.debounce(500).setWishlist({{ $productId }}, wished)" aria-label="Add to wishlist"><x-icon name="heart" /></button>
        @else
            <button type="button" class="pcard__wish" disabled aria-label="Add to wishlist"><x-icon name="heart" /></button>
        @endif
    </a>
    <div class="pcard__body">
        <a href="{{ $productUrl }}" class="pcard__name">{{ $product['name'] }}</a>
        <div class="pcard__row">
            @if ($hasSale)
                <span style="display:flex;gap:8px;align-items:baseline">
                    <span class="price">৳{{ number_format($product['sale']) }}</span>
                    <span class="price price--old">৳{{ number_format($product['price']) }}</span>
                </span>
            @else
                <span class="price">৳{{ number_format($product['price']) }}</span>
            @endif
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
