<div class="jtc-relblock" aria-label="Related products loading">
    <div class="jtc-relblock__head">
        <h2 class="jtc-relblock__title">You may also like</h2>
    </div>
    <div class="jtc-shopgrid">
        @foreach(range(1, 4) as $i)
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
