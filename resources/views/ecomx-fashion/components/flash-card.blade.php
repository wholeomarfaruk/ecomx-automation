@props(['item'])
@php
    $save = $item['price'] - $item['sale'];
    $productId = $item['id'] ?? null;
    $isWished = $item['is_wished'] ?? false;
    $offerBadges = $productId && empty($item['demo']) ? app(\App\Services\OfferService::class)->badgesForProductId((int) $productId) : [];
    // Demo items (no real product) have no page to open.
    $productUrl = $item['url'] ?? (! empty($item['slug']) ? route('ecomx-fashion.product', $item['slug']) : '#');
    // products.stock_status as set in the admin — absent on demo items.
    $stockState = $item['stock_status'] ?? null;
    $soldOut = $stockState === 'out_of_stock';
@endphp
<div class="fcard {{ $soldOut ? 'is-soldout' : '' }}" x-data="{ added:false, wished: @js($isWished) }">
    <a href="{{ $productUrl }}" class="fcard__media">
        <x-ux-img :id="$item['img']" :w="500" :alt="$item['name']" class="fcard__img" />
        <span class="fcard__save">Save <span class="sym">৳</span>{{ number_format($save) }}</span>
        @if($soldOut)
            <span class="pcard__stock pcard__stock--out">Sold out</span>
        @elseif($stockState === 'low_stock')
            <span class="pcard__stock pcard__stock--low pcard__stock--below">Low stock</span>
        @endif
        @if($offerBadges !== [] && ! $soldOut)
            <span class="pcard__offer" title="{{ $offerBadges[0]['name'] }}">🎁 {{ $offerBadges[0]['label'] }}@if(count($offerBadges) > 1) +{{ count($offerBadges) - 1 }}@endif</span>
        @endif
        @if ($productId)
            <button type="button" class="pcard__wish" :class="wished && 'is-on'" @click.prevent="wished = !wished" wire:click.prevent.debounce.500ms="setWishlist({{ $productId }}, wished)" aria-label="Wishlist"><x-icon name="heart" /></button>
        @else
            <button type="button" class="pcard__wish" disabled aria-label="Wishlist"><x-icon name="heart" /></button>
        @endif
    </a>
    <div style="display:flex;flex-direction:column;gap:4px;padding:0 4px">
        <a href="{{ $productUrl }}" class="fcard__name" style="color:inherit;text-decoration:none">{{ $item['name'] }}</a>
        <div style="display:flex;justify-content:space-between;align-items:baseline">
            <span style="display:flex;gap:8px;align-items:baseline">
                <span class="fcard__price"><span class="sym">৳</span>{{ number_format($item['sale']) }}</span>
                <span class="fcard__old">৳{{ number_format($item['price']) }}</span>
            </span>
            <div class="pcard__swatches">@foreach($item['colors'] as $col)<span class="pcard__swatch" style="border-color:rgba(248,247,245,.3);background:{{ $col }}"></span>@endforeach</div>
        </div>
    </div>
    @if ($soldOut)
        <button type="button" class="pcard__add" style="border-color:rgba(248,247,245,.35);color:#F8F7F5" disabled>Sold out</button>
    @elseif ($productId)
        <button type="button" class="pcard__add" style="border-color:rgba(248,247,245,.35);color:#F8F7F5" @click="added=true;$dispatch('add-to-cart', { productId: {{ $productId }} });setTimeout(()=>added=false,1600)" x-text="added ? 'Added ✓' : 'Add to Cart'"></button>
    @else
        <button type="button" class="pcard__add" style="border-color:rgba(248,247,245,.35);color:#F8F7F5" disabled>Add to Cart</button>
    @endif
</div>
