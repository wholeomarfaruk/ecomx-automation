<section class="container section" aria-label="Shop by style loading">
    <div class="skel skel--text" style="width:200px;height:22px;margin-bottom:20px"></div>
    <div class="style-grid">
        @foreach([2,1,1,2,1,1] as $span)
            <div class="skel" style="grid-row:span {{ $span }};border-radius:14px"></div>
        @endforeach
    </div>
</section>
