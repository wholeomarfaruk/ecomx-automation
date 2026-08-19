<section class="container section" aria-label="Trending collection">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:24px">
        <div><p class="kicker">{{ $kicker }}</p><h2 class="h-section">{{ $heading }}</h2></div>
        <div style="display:flex;gap:8px">
            <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:-560,behavior:'smooth'})">←</button>
            <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:560,behavior:'smooth'})">→</button>
        </div>
    </div>
    <div class="row-scroll no-scrollbar" data-row style="gap:18px">
        @foreach($trending as $p)
            <div wire:key="trending-{{ $p['id'] ?? $loop->index }}" style="flex:none;width:calc((100% - 72px)/5);min-width:180px"><x-product-card :product="$p" /></div>
        @endforeach
    </div>
</section>
