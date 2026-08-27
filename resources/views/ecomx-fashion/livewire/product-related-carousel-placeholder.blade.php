<section class="section" aria-label="Related products" style="margin-top:clamp(48px,7vw,80px)">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:22px">
        <h2 style="font-size:clamp(24px,2.6vw,32px)">You may also like</h2>
    </div>
    <div class="row-scroll no-scrollbar" style="gap:18px">
        @foreach(range(1, 4) as $i)
            <div style="flex:none;width:calc((100% - 3.3*18px)/4.3);min-width:200px;display:flex;flex-direction:column;gap:10px">
                <div class="skel" style="border-radius:var(--radius);aspect-ratio:3/4"></div>
                <div class="skel" style="width:80%;height:14px;border-radius:4px"></div>
                <div class="skel" style="width:40%;height:12px;border-radius:4px"></div>
            </div>
        @endforeach
    </div>
</section>
