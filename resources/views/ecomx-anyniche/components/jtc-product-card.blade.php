@props(['product', 'rail' => true])
@php
    $p = $product;
    $productId = $p['id'] ?? null;
    $isWished = $p['is_wished'] ?? false;
    $offerBadges = $productId && empty($p['demo']) ? app(\App\Services\OfferService::class)->badgesForProductId((int) $productId) : [];
    // products.stock_status as set in the admin — absent on demo items.
    $stockState = $p['stock_status'] ?? null;
    $soldOut = $stockState === 'out_of_stock';
@endphp
<article {{ $attributes->merge(['class' => 'jtc-card ' . ($rail ? 'jtc-card--rail' : '') . ($soldOut ? ' is-soldout' : '')]) }} x-data="{ wished: @js($isWished) }">
    <div class="jtc-card__media">
        <div class="jtc-card__badges">
            @if($soldOut)<span class="jtc-badge jtc-badge--soldout">Sold out</span>@endif
            @if($stockState === 'low_stock')<span class="jtc-badge jtc-badge--low">Low stock</span>@endif
            @if(!empty($p['showNew']))<span class="jtc-badge jtc-badge--new">New</span>@endif
            @if(!empty($p['showDealPct']))<span class="jtc-badge jtc-badge--deal">{{ $p['pctText'] }}</span>@endif
            @foreach($soldOut ? [] : array_slice($offerBadges, 0, 2) as $offerBadge)
                <span class="jtc-badge jtc-badge--offer" title="{{ $offerBadge['name'] }}">🎁 {{ $offerBadge['label'] }}</span>
            @endforeach
        </div>
        @if($productId)
            <button type="button" class="jtc-wish" aria-label="Save" :class="wished && 'is-wished'"
                @click.prevent="wished = !wished"
                wire:click.prevent.debounce.500ms="setWishlist({{ $productId }}, wished)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"></path></svg>
            </button>
        @endif
        <a href="{{ $p['url'] ?? '#' }}">
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" loading="lazy">
        </a>
    </div>
    <div class="jtc-card__body">
        <a href="{{ $p['url'] ?? '#' }}" class="jtc-card__title">{{ $p['name'] }}</a>
        <div class="jtc-card__prices">
            @if(!empty($p['priceIsCompare']))
                <span class="jtc-card__price jtc-card__price--sale">{{ $p['priceText'] }}</span>
                <span class="jtc-card__was">{{ $p['compareText'] }}</span>
            @else
                <span class="jtc-card__price">{{ $p['priceText'] }}</span>
            @endif
        </div>
        @if($soldOut)
            <button type="button" class="jtc-btn jtc-btn--primary jtc-card__add" disabled>Sold out</button>
        @elseif($productId)
            <button type="button" class="jtc-btn jtc-btn--primary jtc-card__add"
                @click="$dispatch('add-to-cart', { productId: {{ $productId }} })">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M6 6h15l-1.5 9h-12z"></path><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle></svg>
                Add to cart
            </button>
        @endif
    </div>
</article>
