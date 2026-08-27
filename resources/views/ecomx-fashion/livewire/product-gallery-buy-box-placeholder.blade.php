<div class="pdp">
    {{-- GALLERY skeleton --}}
    <div class="gallery">
        <div class="gallery__thumbs no-scrollbar">
            @foreach(range(1, 5) as $i)
                <div class="gallery__thumb skel"></div>
            @endforeach
        </div>
        <div class="gallery__main skel"></div>
    </div>

    {{-- INFO skeleton --}}
    <div class="pdp__info">
        <div>
            <div class="skel" style="width:110px;height:11px;border-radius:4px;margin-bottom:10px"></div>
            <div class="skel" style="width:70%;height:32px;border-radius:6px;margin-bottom:14px"></div>
            <div class="skel" style="width:140px;height:20px;border-radius:4px"></div>
        </div>

        <div class="skel" style="width:100%;height:60px;border-radius:6px"></div>

        <div>
            <div class="skel" style="width:120px;height:12px;border-radius:4px;margin-bottom:10px"></div>
            <div style="display:flex;gap:10px">
                @foreach(range(1, 4) as $i)
                    <div class="swatch-sq skel"></div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="skel" style="width:80px;height:12px;border-radius:4px;margin-bottom:10px"></div>
            <div class="size-grid">
                @foreach(range(1, 5) as $i)
                    <div class="size-btn skel"></div>
                @endforeach
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px">
            <div style="display:flex;gap:10px">
                <div class="skel btn btn--block" style="flex:1;height:48px"></div>
                <div class="skel" style="width:54px;height:48px;border-radius:12px"></div>
            </div>
            <div style="display:flex;gap:10px">
                <div class="skel btn btn--block" style="flex:1;height:48px"></div>
                <div class="skel" style="width:54px;height:48px;border-radius:12px"></div>
            </div>
        </div>

        <div class="skel" style="width:100%;height:64px;border-radius:8px"></div>
    </div>
</div>
