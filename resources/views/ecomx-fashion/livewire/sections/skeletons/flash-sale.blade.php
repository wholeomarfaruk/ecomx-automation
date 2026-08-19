<section class="container section" aria-label="Flash sale loading">
    <div class="skel" style="border-radius:14px;padding:clamp(20px,3vw,40px);background:rgba(var(--sec-rgb),.06)">
        <div class="skel skel--text" style="width:280px;height:28px;margin-bottom:24px"></div>
        <div class="row-scroll no-scrollbar" style="gap:16px">
            @foreach(range(1, 4) as $skeletonIndex)
                <div class="skel" style="flex:none;width:calc((100% - 4.3*16px)/4.3);min-width:200px;aspect-ratio:3/4"></div>
            @endforeach
        </div>
    </div>
</section>
