<section class="section" aria-label="Reviews" style="margin-top:clamp(56px,8vw,96px)">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:20px;flex-wrap:wrap">
        <div><p class="kicker">★ 4.8 · 2,314 reviews</p><h2 class="h-section">Loved by our customers</h2></div>
        <div class="skel" style="width:150px;height:44px;border-radius:999px"></div>
    </div>
    <div class="row-scroll no-scrollbar" style="gap:20px">
        @foreach(range(1, 4) as $i)
            <div style="flex:none;width:290px">
                <div class="skel" style="border-radius:var(--radius);aspect-ratio:9/16"></div>
            </div>
        @endforeach
    </div>
</section>
