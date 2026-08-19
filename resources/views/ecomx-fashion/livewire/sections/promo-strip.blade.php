<section class="container section" aria-label="Promo">
    <a href="{{ $bannerLink ?: route('ecomx-fashion.shop') }}" style="display:block;border-radius:14px;overflow:hidden;background:#ECE9E3;aspect-ratio:21/4">
        <x-ux-img :id="$bannerUrl" :w="1800" alt="New arrivals" style="width:100%;height:100%;object-fit:cover" />
    </a>
</section>
