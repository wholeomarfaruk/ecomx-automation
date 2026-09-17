<div>
@if(count($slides) > 0)
    <section
        class="jtc-section jtc-section--hero"
        x-data="{
            slides: @js($slides),
            heroIndex: 0,
            _timer: null,
            get slideCount() { return this.slides.length },
            heroGo(n) { const t = this.slideCount; this.heroIndex = ((n % t) + t) % t },
            heroPrev() { this.heroGo(this.heroIndex - 1); this._restart() },
            heroNext() { this.heroGo(this.heroIndex + 1); this._restart() },
            _restart() { clearInterval(this._timer); this._timer = setInterval(() => this.heroGo(this.heroIndex + 1), 5500) },
            init() { this._restart() },
            destroy() { clearInterval(this._timer) },
        }"
    >
        <div class="jtc-shell">
            <div class="jtc-hero">
                <div class="jtc-hero__slider">
                    @foreach($slides as $i => $slide)
                        <div class="jtc-hero__slide" :class="heroIndex === {{ $i }} && 'is-active'" @if($i === 0) style="opacity:1;visibility:visible" @endif>
                            @if($slide['link'])
                                <a href="{{ $slide['link'] }}">
                                    <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}" @if($i === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif>
                                </a>
                            @else
                                <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] }}" @if($i === 0) loading="eager" fetchpriority="high" @else loading="lazy" @endif>
                            @endif
                        </div>
                    @endforeach

                    <div class="jtc-hero__dots">
                        @foreach($slides as $i => $slide)
                            <button class="jtc-hero__dot" :class="heroIndex === {{ $i }} && 'is-active'" aria-label="Go to slide {{ $i + 1 }}" @click="heroGo({{ $i }}); _restart()"></button>
                        @endforeach
                    </div>

                    <div class="jtc-hero__nav">
                        <button class="jtc-hero__arrow" aria-label="Previous" @click="heroPrev()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <button class="jtc-hero__arrow" aria-label="Next" @click="heroNext()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>

                @if(count($sideBanners) > 0)
                    <div class="jtc-hero__side">
                        @foreach($sideBanners as $banner)
                            <a href="{{ $banner['link'] ?: '#' }}" class="jtc-hero__banner">
                                <img src="{{ $banner['image'] }}" alt="{{ $banner['title'] }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
</div>
