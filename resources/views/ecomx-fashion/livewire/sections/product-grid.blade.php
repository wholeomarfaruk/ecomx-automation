<section class="container section" aria-label="{{ $heading }}">
    <div style="margin-bottom:24px"><p class="kicker">{{ $kicker }}</p><h2 class="h-section">{{ $heading }}</h2></div>
    <div class="product-grid">
        @foreach($products as $p)
            <div wire:key="product-grid-{{ $p['id'] ?? $loop->index }}"><x-product-card :product="$p" /></div>
        @endforeach
    </div>
    <div style="display:flex;justify-content:center;margin-top:28px">
        <a href="{{ route('ecomx-fashion.shop') }}" class="btn btn--outline">{{ $buttonLabel }}</a>
    </div>
</section>
