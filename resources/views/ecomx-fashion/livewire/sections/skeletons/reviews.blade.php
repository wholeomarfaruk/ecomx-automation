<section class="container section" aria-label="Customer reviews loading">
    <div class="skel skel--text" style="width:260px;height:22px;margin-bottom:20px"></div>
    <div class="row-scroll no-scrollbar" style="gap:20px">
        @foreach(range(1, 4) as $skeletonIndex)
            <div class="skel" style="flex:none;width:280px;aspect-ratio:3/4;border-radius:14px"></div>
        @endforeach
    </div>
</section>
