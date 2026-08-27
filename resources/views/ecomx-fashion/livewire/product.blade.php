<div class="container section" style="margin-top:0">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);padding:20px 0 16px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <a href="{{ route('ecomx-fashion.category') }}" style="color:rgba(var(--pri-rgb),.5)">{{ $product['cat'] }}</a> / <span style="color:var(--pri)">{{ $product['name'] }}</span></nav>

    {{-- Gallery + buy box: above-the-fold, loads immediately after shell paints (no scroll-wait) --}}
    <livewire:ecomx-fashion.product-gallery-buy-box :product-id="$productId" :key="'gallery-'.$productId" />

    {{-- REVIEWS: lazy-loaded with its own skeleton; owns its own rating summary + write-review form --}}
    <livewire:ecomx-fashion.product-reviews-slider :product-id="$productId" :key="'reviews-'.$productId" />

    {{-- RELATED carousel: below the fold, loads when scrolled into view --}}
    <livewire:ecomx-fashion.product-related-carousel :product-id="$productId" :key="'related-'.$productId" />

    <x-marketing.events :events="$marketingEvents" />
</div>
