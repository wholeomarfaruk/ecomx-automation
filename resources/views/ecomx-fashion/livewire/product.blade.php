<div class="container section" style="margin-top:0">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);padding:20px 0 16px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <a href="{{ route('ecomx-fashion.category') }}" style="color:rgba(var(--pri-rgb),.5)">{{ $product['cat'] }}</a> / <span style="color:var(--pri)">{{ $product['name'] }}</span></nav>

    {{-- All three below load on-mount ('lazy' => 'on-load'), not on scroll --}}
    {{-- intersection, so they start fetching right after the page loads and --}}
    {{-- are ready (or close to it) by the time the visitor scrolls to them. --}}

    {{-- Gallery + buy box: above-the-fold, loads immediately after shell paints (no scroll-wait) --}}
    <livewire:ecomx-fashion.product-gallery-buy-box :product-id="$productId" :key="'gallery-'.$productId" lazy="on-load" />

    {{-- REVIEWS: lazy-loaded with its own skeleton; owns its own rating summary + write-review form --}}
    <livewire:ecomx-fashion.product-reviews-slider :product-id="$productId" :key="'reviews-'.$productId" lazy="on-load" />

    {{-- RELATED carousel: below the fold, loads on-mount, no scroll-wait --}}
    <livewire:ecomx-fashion.product-related-carousel :product-id="$productId" :key="'related-'.$productId" lazy="on-load" />

    <x-marketing.events :events="$marketingEvents" />
</div>
