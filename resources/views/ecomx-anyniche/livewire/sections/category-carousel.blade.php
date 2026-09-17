<div>
@if(count($items) > 0)
    <section class="jtc-section" x-data="catCarousel(@js($items))">
        <div class="jtc-shell">
            <div class="jtc-section-head">
                <h2 class="jtc-h2">Shop by category</h2>
                <div class="jtc-carousel-nav">
                    <button class="jtc-carousel-nav__prev" aria-label="Previous categories" @click="catPrev()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button class="jtc-carousel-nav__next" aria-label="Next categories" @click="catNextManual()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>

            <div class="jtc-catcarousel" x-ref="catViewport">
                <div class="jtc-catcarousel__track" :style="`transform:${catTransform};transition:${catTransition}`">
                    <template x-for="(cat, i) in catItems" :key="cat.id ?? `${cat.name}-${i}`">
                        <a :href="cat.url" class="jtc-catcard">
                            <div class="jtc-catcard__inner">
                                <div class="jtc-catcard__img">
                                    <img :src="cat.image" :alt="cat.name" loading="lazy">
                                </div>
                                <div class="jtc-catcard__name" x-text="cat.name"></div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </section>
@endif
</div>
