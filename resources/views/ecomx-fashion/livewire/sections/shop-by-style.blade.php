<section class="container section" aria-label="Shop by style">
    <x-section-head title="Shop by style" :link="route('ecomx-fashion.shop')" link-label="All styles →" />
    <div class="style-grid">
        @foreach($styles as $s)
            <a href="{{ isset($s['slug']) ? route('ecomx-fashion.category', $s['slug']) : route('ecomx-fashion.shop') }}" class="cat-card reveal" style="grid-row:span {{ $s['span'] }}">
                <x-ux-img :id="$s['img']" :w="700" :alt="$s['name']" />
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(var(--pri-rgb),.4),transparent 45%)"></div>
                <div style="position:absolute;left:18px;bottom:16px;right:18px"><p style="font-family:var(--fh);font-size:22px;color:var(--sec);font-family:'Playfair Display',serif">{{ $s['name'] }}</p><p style="font-size:12px;color:rgba(var(--sec-rgb),.75)">{{ $s['sub'] }}</p></div>
            </a>
        @endforeach
    </div>
</section>
