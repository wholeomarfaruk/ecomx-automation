<div class="marquee section" aria-hidden="true">
    <div class="marquee__track">
        @foreach(array_merge($items, $items) as $m)
            <span class="marquee__item">{{ $m }}<span style="color:var(--ac3);font-size:10px">✦</span></span>
        @endforeach
    </div>
</div>
