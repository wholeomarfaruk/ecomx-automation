<div class="jtc-pd__shell">
    <div class="jtc-pd__crumb">
        <a href="{{ route('ecomx-anyniche.home') }}">Home</a><span class="sep">/</span>
        <a href="{{ route('ecomx-anyniche.shop') }}">Shop</a><span class="sep">/</span>
        @if($product['cat'])
            <a href="{{ route('ecomx-anyniche.category', $product['catSlug']) }}">{{ $product['cat'] }}</a><span class="sep">/</span>
        @endif
        <span style="color:#14201c;font-weight:600">{{ $product['name'] }}</span>
    </div>

    {{-- All three below load on-mount ('lazy' => 'on-load'), not on scroll --}}
    {{-- intersection, so they start fetching right after the page loads and --}}
    {{-- are ready (or close to it) by the time the visitor scrolls to them. --}}

    {{-- Gallery + buy box: above-the-fold, loads immediately after shell paints (no scroll-wait) --}}
    <livewire:ecomx-anyniche.product-gallery-buy-box :product-id="$productId" :key="'gallery-'.$productId" lazy="on-load" />

    {{-- REVIEWS: lazy-loaded with its own skeleton; owns its own rating summary + write-review form --}}
    <livewire:ecomx-anyniche.product-reviews-slider :product-id="$productId" :key="'reviews-'.$productId" lazy="on-load" />

    {{-- RELATED carousel: below the fold, loads on-mount, no scroll-wait --}}
    <livewire:ecomx-anyniche.product-related-carousel :product-id="$productId" :key="'related-'.$productId" lazy="on-load" />

    <x-marketing.events :events="$marketingEvents" />
</div>
