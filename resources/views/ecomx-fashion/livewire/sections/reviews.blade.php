<section class="container section" aria-label="Customer reviews">
    <x-section-head :kicker="$kicker" :title="$heading" :link="route('ecomx-fashion.reviews')" :link-label="$linkLabel" />
    <x-review-slider :reviews="$reviews" />
</section>
