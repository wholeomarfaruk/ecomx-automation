<section class="container section" aria-label="Categories">
    <x-section-head title="Shop by category" :link="route('ecomx-fashion.shop')" />
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:16px">
        @foreach($categories as $c)
            <a href="{{ route('ecomx-fashion.category', $c['slug']) }}" class="cat-card reveal">
                <x-ux-img :id="$c['image'] ?? 'photo-1441986300917-64674bd600d8'" :w="700" :alt="$c['name']" />
                <div style="position:absolute;left:16px;bottom:16px;background:rgba(var(--sec-rgb),.94);border-radius:999px;padding:10px 18px;display:flex;gap:10px;align-items:center">
                    <span style="font-size:13px;font-weight:600">{{ $c['name'] }}</span><span class="muted" style="font-size:11.5px">{{ $c['count'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
