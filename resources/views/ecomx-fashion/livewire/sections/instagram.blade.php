<section class="container section" aria-label="Instagram gallery">
    <x-section-head title="@seldomfashion" :link="route('ecomx-fashion.home')" link-label="Follow us →" />
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,160px),1fr));gap:12px">
        @foreach($instagram as $g)
            <a href="{{ route('ecomx-fashion.home') }}" class="cat-card reveal" style="aspect-ratio:1">
                <x-ux-img :id="$g['img']" :w="500" alt="Instagram post" />
                <span style="position:absolute;left:10px;bottom:10px;background:rgba(var(--pri-rgb),.55);color:var(--sec);font-size:11px;padding:5px 10px;border-radius:999px">♡ {{ $g['likes'] }}</span>
            </a>
        @endforeach
    </div>
</section>
