<section class="container section" aria-label="Why Seldom Fashion loading">
    <div class="faq">
        <div style="display:flex;flex-direction:column;gap:24px">
            <div class="skel skel--text" style="width:70%;height:36px"></div>
            <div class="skel skel--text" style="width:90%;height:60px"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                @foreach(range(1, 4) as $skeletonIndex)
                    <div class="skel" style="border-radius:12px;height:70px"></div>
                @endforeach
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px">
            @foreach(range(1, 6) as $skeletonIndex)
                <div class="skel skel--text" style="height:52px"></div>
            @endforeach
        </div>
    </div>
</section>
