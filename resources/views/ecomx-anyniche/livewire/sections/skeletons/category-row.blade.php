<section class="jtc-section">
    <div class="jtc-shell">
        <div class="jtc-skel-head">
            <div class="jtc-skel-head__title"></div>
            <div class="jtc-skel-head__link"></div>
        </div>
        <div class="{{ $rail ? 'jtc-rail' : 'jtc-grid' }}">
            @foreach(range(1, $rail ? 6 : 12) as $i)
                <div class="jtc-skel-card">
                    <div class="jtc-skel-card__media"></div>
                    <div class="jtc-skel-card__body">
                        <div class="jtc-skel-card__title"></div>
                        <div class="jtc-skel-card__title2"></div>
                        <div class="jtc-skel-card__price"></div>
                        <div class="jtc-skel-card__btn"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
