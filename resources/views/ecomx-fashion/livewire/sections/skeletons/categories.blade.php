<section class="container section" aria-label="Categories loading">
    <div class="skel skel--text" style="width:220px;height:22px;margin-bottom:20px"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:16px">
        @foreach(range(1, 3) as $skeletonIndex)
            <div class="skel cat-card" style="aspect-ratio:3/4"></div>
        @endforeach
    </div>
</section>
