<section class="container section" aria-label="Instagram gallery loading">
    <div class="skel skel--text" style="width:180px;height:22px;margin-bottom:20px"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,160px),1fr));gap:12px">
        @foreach(range(1, 6) as $skeletonIndex)
            <div class="skel" style="aspect-ratio:1;border-radius:14px"></div>
        @endforeach
    </div>
</section>
