<div class="jtc-pd__main">
    {{-- GALLERY skeleton --}}
    <div class="jtc-gallery">
        <div class="jtc-gallery__thumbs no-scrollbar">
            @foreach(range(1, 5) as $i)
                <div class="jtc-gallery__thumb skel"></div>
            @endforeach
        </div>
        <div class="jtc-gallery__viewer skel"></div>
    </div>

    {{-- INFO skeleton --}}
    <div class="jtc-pd-info">
        <div class="skel" style="width:110px;height:11px;border-radius:4px;margin-bottom:10px"></div>
        <div class="skel" style="width:70%;height:32px;border-radius:6px;margin-bottom:14px"></div>
        <div class="skel" style="width:140px;height:32px;border-radius:4px;margin-bottom:18px"></div>
        <div class="skel" style="width:100%;height:60px;border-radius:6px;margin-bottom:22px"></div>

        <div style="margin-bottom:22px">
            <div class="skel" style="width:120px;height:12px;border-radius:4px;margin-bottom:10px"></div>
            <div style="display:flex;gap:10px">
                @foreach(range(1, 4) as $i)
                    <div class="skel" style="width:42px;height:42px;border-radius:50%"></div>
                @endforeach
            </div>
        </div>

        <div style="margin-bottom:22px">
            <div class="skel" style="width:80px;height:12px;border-radius:4px;margin-bottom:10px"></div>
            <div style="display:flex;gap:8px">
                @foreach(range(1, 5) as $i)
                    <div class="skel" style="width:46px;height:40px;border-radius:7px"></div>
                @endforeach
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px">
            <div style="display:flex;gap:10px">
                <div class="skel" style="flex:1;height:48px;border-radius:7px"></div>
                <div class="skel" style="flex:1;height:48px;border-radius:7px"></div>
            </div>
        </div>
    </div>
</div>
