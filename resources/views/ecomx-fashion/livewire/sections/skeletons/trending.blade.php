<section class="container section" aria-label="Trending collection loading">
    <div class="skel skel--text" style="width:240px;height:26px;margin-bottom:24px"></div>
    <div class="row-scroll no-scrollbar" style="gap:18px">
        @foreach(range(1, 5) as $skeletonIndex)
            <div class="skel" style="flex:none;width:calc((100% - 72px)/5);min-width:180px;aspect-ratio:3/4"></div>
        @endforeach
    </div>
</section>
