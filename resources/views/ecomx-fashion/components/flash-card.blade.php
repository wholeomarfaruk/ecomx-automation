@props(['item'])
@php
    $save = $item['price'] - $item['sale'];
    $productId = $item['id'] ?? null;
    $isWished = $item['is_wished'] ?? false;
@endphp
<div class="fcard" x-data="{ added:false, wished: @js($isWished) }">
    <a href="{{ $item['url'] ?? route('ecomx-fashion.product') }}" class="fcard__media">
        <x-ux-img :id="$item['img']" :w="500" :alt="$item['name']" class="fcard__img" />
        <span class="fcard__save">Save <span class="sym">৳</span>{{ number_format($save) }}</span>
        @if ($productId)
            <button type="button" class="pcard__wish" :class="wished && 'is-on'" @click.prevent="wished = !wished; $wire.debounce(500).setWishlist({{ $productId }}, wished)" aria-label="Wishlist"><x-icon name="heart" /></button>
        @else
            <button type="button" class="pcard__wish" disabled aria-label="Wishlist"><x-icon name="heart" /></button>
        @endif
    </a>
    <div style="display:flex;flex-direction:column;gap:4px;padding:0 4px">
        <span class="fcard__name">{{ $item['name'] }}</span>
        <div style="display:flex;justify-content:space-between;align-items:baseline">
            <span style="display:flex;gap:8px;align-items:baseline">
                <span class="fcard__price"><span class="sym">৳</span>{{ number_format($item['sale']) }}</span>
                <span class="fcard__old">৳{{ number_format($item['price']) }}</span>
            </span>
            <div class="pcard__swatches">@foreach($item['colors'] as $col)<span class="pcard__swatch" style="border-color:rgba(248,247,245,.3);background:{{ $col }}"></span>@endforeach</div>
        </div>
    </div>
    @if ($productId)
        <button type="button" class="pcard__add" style="border-color:rgba(248,247,245,.35);color:#F8F7F5" @click="added=true;$dispatch('add-to-cart', { productId: {{ $productId }} });setTimeout(()=>added=false,1600)" x-text="added ? 'Added ✓' : 'Add to Cart'"></button>
    @else
        <button type="button" class="pcard__add" style="border-color:rgba(248,247,245,.35);color:#F8F7F5" disabled>Add to Cart</button>
    @endif
</div>
