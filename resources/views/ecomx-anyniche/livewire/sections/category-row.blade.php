<div>
@if(count($products) > 0)
    <section class="jtc-section">
        <div class="jtc-shell">
            <div class="jtc-section-head">
                <h2 class="jtc-h2">{{ $category->name ?? 'Featured' }}</h2>
                @if($category?->slug)
                    <a href="{{ route('ecomx-anyniche.category', $category->slug) }}" class="jtc-seeall">
                        See all
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                @endif
            </div>
            <div class="{{ $rail ? 'jtc-rail' : 'jtc-grid' }}">
                @foreach($products as $p)
                    <x-anyniche::jtc-product-card :product="$p" :rail="$rail" wire:key="cat-row-{{ $sectionKey }}-{{ $p['id'] ?? $loop->index }}" />
                @endforeach
            </div>
        </div>
    </section>
@endif
</div>
