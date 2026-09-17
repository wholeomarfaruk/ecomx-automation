<div class="jtc-relblock" aria-label="Related products">
    <div class="jtc-relblock__head">
        <h2 class="jtc-relblock__title">You may also like</h2>
        <a href="{{ route('ecomx-anyniche.shop') }}" class="jtc-relblock__more">
            View more
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>
    <div class="jtc-shopgrid">
        @foreach($related as $p)
            <x-anyniche::jtc-product-card :product="$p" :rail="false" wire:key="related-{{ $p['id'] ?? $loop->index }}" />
        @endforeach
    </div>
</div>
