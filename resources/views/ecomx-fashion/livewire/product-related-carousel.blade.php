<section class="section reveal" aria-label="Related products" style="margin-top:clamp(48px,7vw,80px)">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:22px">
        <h2 style="font-size:clamp(24px,2.6vw,32px)">You may also like</h2>
        <div style="display:flex;gap:8px">
            <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:-600,behavior:'smooth'})">←</button>
            <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:600,behavior:'smooth'})">→</button>
        </div>
    </div>
    <div class="row-scroll no-scrollbar" data-row style="gap:18px">
        @foreach($related as $p)
            <div wire:key="related-{{ $p['id'] ?? $loop->index }}" style="flex:none;width:calc((100% - 3.3*18px)/4.3);min-width:200px"><x-product-card :product="$p" /></div>
        @endforeach
    </div>
</section>
