<div>
@if($image)
    <section class="jtc-section jtc-section--strip">
        <div class="jtc-shell">
            <a href="{{ $link ?: '#' }}" class="jtc-promo" style="height:100px;min-height:0">
                <img src="{{ $image }}" alt="">
            </a>
        </div>
    </section>
@endif
</div>
