<div>
<section class="jtc-section">
    <div class="jtc-shell">
        <div class="jtc-section-head">
            <h2 class="jtc-h2">Browse our products</h2>
            <a href="{{ route('ecomx-anyniche.shop') }}" class="jtc-seeall">
                See all
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
        <div class="jtc-grid">
            @foreach($products as $p)
                <x-anyniche::jtc-product-card :product="$p" :rail="false" wire:key="browse-all-{{ $p['id'] ?? $loop->index }}" />
            @endforeach
        </div>
    </div>
</section>
</div>
