<section class="container section" aria-label="Flash sale">
    <div class="flash">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;margin-bottom:24px">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                <span class="flash__badge">{{ $badge }}</span>
                <h2 style="color:var(--sec);font-size:clamp(24px,2.6vw,34px)">{{ $heading }}</h2>
                <div class="flash__timer" style="display:flex;align-items:center;gap:6px" x-data="countdown()">
                    <span x-text="h"></span><span style="color:var(--ac3)">:</span><span x-text="m"></span><span style="color:var(--ac3)">:</span><span x-text="s"></span>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center" x-data="carousel()">
                <a href="{{ route('ecomx-fashion.shop') }}" class="btn btn--pill" style="background:var(--sec);color:var(--pri);padding:12px 22px">{{ $buttonLabel }}</a>
                <button class="icon-btn" style="background:none;color:var(--sec);border-color:rgba(255,255,255,.25)" @click="$root.closest('.flash').querySelector('[x-ref=frow]').scrollBy({left:-400,behavior:'smooth'})">←</button>
                <button class="icon-btn" style="background:none;color:var(--sec);border-color:rgba(255,255,255,.25)" @click="$root.closest('.flash').querySelector('[x-ref=frow]').scrollBy({left:400,behavior:'smooth'})">→</button>
            </div>
        </div>
        <div class="row-scroll no-scrollbar" x-ref="frow" style="gap:16px">
            @foreach($flashSale as $item)
                <div wire:key="flash-{{ $item['id'] ?? $loop->index }}" style="flex:none;width:calc((100% - 4.3*16px)/4.3);min-width:200px">
                    <x-flash-card :item="$item" />
                </div>
            @endforeach
        </div>
    </div>
</section>
