<section class="container section" aria-label="Product grid loading">
    <div class="skel skel--text" style="width:240px;height:26px;margin-bottom:24px"></div>
    <div class="product-grid">
        @foreach(range(1, 10) as $skeletonIndex)
            <div class="skel" style="aspect-ratio:3/4"></div>
        @endforeach
    </div>
</section>
