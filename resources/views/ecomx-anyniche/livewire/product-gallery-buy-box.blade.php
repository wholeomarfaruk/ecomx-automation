<div class="jtc-pd__main" x-data="{
    qty: 1,
    inc() { this.qty += 1; },
    dec() { this.qty = Math.max(1, this.qty - 1); },
}">
    {{-- GALLERY --}}
    <div class="jtc-gallery">
        <div class="jtc-gallery__thumbs no-scrollbar" x-data="gallerySwiperThumbs({{ $activeIndex }})" wire:key="gallery-thumbs-{{ md5(json_encode($media)) }}">
            @foreach($media as $i => $m)
                <button type="button" class="jtc-gallery__thumb" :class="active === {{ $i }} && 'is-active'" @click="goto({{ $i }})">
                    <img src="{{ $m['img'] }}" alt="">
                    @if($m['video'])
                        <span class="jtc-gallery__play"><svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M8 5v14l11-7z"></path></svg></span>
                    @endif
                </button>
            @endforeach
        </div>
        <div class="jtc-gallery__viewer swiper" x-data="gallerySwiper" x-init="init({{ $activeIndex }})" wire:key="gallery-main-{{ md5(json_encode($media)) }}" wire:ignore>
            <div class="swiper-wrapper">
                @foreach($media as $i => $m)
                    <a class="swiper-slide" href="{{ $m['img'] }}" data-fancybox="pdp-gallery" @if($m['video']) data-type="html5video" @endif>
                        <img src="{{ $m['img'] }}" alt="{{ $product['name'] }}">
                        @if($m['video'])
                            <span class="jtc-gallery__play jtc-gallery__play--lg"><svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M8 5v14l11-7z"></path></svg></span>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="swiper-button-prev" x-show="slideCount > 1"></div>
            <div class="swiper-button-next" x-show="slideCount > 1"></div>
            <div class="jtc-gallery__badges">
                @if($flashSale)
                    <span class="jtc-badge jtc-badge--deal">Sale</span>
                @endif
            </div>
        </div>
    </div>

    {{-- INFO --}}
    <div class="jtc-pd-info">
        <div class="jtc-pd-info__brandrow">
            <span class="jtc-pd-info__brand">{{ $product['cat'] }}</span>
        </div>

        <h1 class="jtc-pd-info__title">{{ $product['name'] }}</h1>

        <div class="jtc-pd-info__price">
            <span class="jtc-pd-info__price-now">৳{{ number_format($this->currentPrice) }}</span>
            @if($this->currentComparePrice !== null)
                <span class="jtc-pd-info__price-was">৳{{ number_format($this->currentComparePrice) }}</span>
                <span class="jtc-pd-info__save">Save ৳{{ number_format($this->currentComparePrice - $this->currentPrice) }}</span>
            @endif
        </div>

        @if($offers !== [])
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin:-4px 0 12px">
                @foreach($offers as $offer)
                    <span class="jtc-pd-info__save">🎁 {{ $offer['label'] }} · {{ $offer['name'] }}</span>
                @endforeach
            </div>
        @endif

        @if($product['desc'])
            <div class="jtc-pd-info__short">{!! $product['desc'] !!}</div>
        @endif

        @if($hasColors)
            {{-- Colours --}}
            <div class="jtc-pd-swatches-wrap">
                <div class="jtc-pd-info__brand" style="display:block;margin-bottom:10px;color:#14201c">Colour — {{ $colors[$selectedColorIndex]['name'] ?? '' }}</div>
                <div class="jtc-pd-swatches">
                    @foreach($colors as $i => $c)
                        <button type="button" class="jtc-pd-swatch {{ $selectedColorIndex === $i ? 'is-active' : '' }}"
                            @if(empty($c['image'])) style="background:{{ $c['hex'] }}" @endif
                            wire:click="selectColor({{ $i }})" aria-label="{{ $c['name'] }}">
                            @if(!empty($c['image']))
                                <img src="{{ $c['image'] }}" alt="">
                            @endif
                            @if($selectedColorIndex === $i)
                                <span class="jtc-pd-swatch__check">✓</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if($hasSizes)
            {{-- Sizes --}}
            <div class="jtc-pd-sizes">
                <div class="jtc-pd-sizes__head">
                    <span class="jtc-pd-info__brand" style="color:#14201c">Size @if($selectedSize) — {{ $selectedSize }} @endif</span>
                    <button type="button" class="jtc-pd-sizes__guide" wire:click="toggleSizeGuide">Size guide</button>
                </div>
                <div class="jtc-pd-sizes__grid">
                    @foreach($sizes as $z)
                        <button type="button" class="jtc-pd-size {{ $selectedSize === $z ? 'is-active' : '' }}" @if($this->isSizeOutOfStock($z)) disabled @endif wire:click="selectSize('{{ $z }}')">
                            {{ $z }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="jtc-qty">
            <button type="button" @click="dec()" aria-label="Decrease">−</button>
            <span x-text="qty"></span>
            <button type="button" @click="inc()" aria-label="Increase">+</button>
        </div>

        <div class="jtc-pd-info__buyrow" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <button type="button" class="jtc-pd-actions__buy" wire:click="addToCart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M13 2 3 14h7v8l10-12h-7z"></path></svg>
                Buy now
            </button>

            <button type="button" class="jtc-pd-actions__cart" wire:click="addToCart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M6 6h15l-1.5 9h-12z"></path><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle></svg>
                {{ $addedToCart ? 'Added ✓' : 'Add to cart' }}
            </button>
        </div>

        <button type="button" class="jtc-pd-actions__wish {{ $this->isWished ? 'is-wished' : '' }}"
            wire:click="toggleWishlist({{ $productId }}, {{ $this->selectedVariantId ?? 'null' }})"
            wire:loading.attr="disabled" wire:target="toggleWishlist({{ $productId }})">
            <svg viewBox="0 0 24 24" fill="{{ $this->isWished ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9Z"></path></svg>
            {{ $this->isWished ? 'Saved to wishlist' : 'Add to wishlist' }}
        </button>

        <div class="jtc-pd-actions__order">
            <a href="https://wa.me/8801700000000?text={{ urlencode('Hi, I want to order: ' . $product['name']) }}" target="_blank" rel="noopener" class="jtc-pd-actions__wa">
                <svg viewBox="0 0 24 24" fill="currentColor" width="19" height="19"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.52 11.99c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.98-.14.16-.29.18-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.16 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"></path></svg>
                Order on WhatsApp
            </a>
            <a href="tel:+8801700000000" class="jtc-pd-actions__call">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.94.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.87.35 1.81.59 2.81.72A2 2 0 0 1 22 16.92z"></path></svg>
                Call for order
            </a>
        </div>

        <div class="jtc-pd-info__reassure">
            <div class="jtc-pd-info__reassure-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="#1B7FC4" stroke-width="2" width="18" height="18"><path d="M3 7h13v8H3z"></path><path d="M16 10h3l2 2v3h-5z"></path><circle cx="7" cy="18" r="1.6"></circle><circle cx="17" cy="18" r="1.6"></circle></svg>
                Delivery in 24–72h
            </div>
            <div class="jtc-pd-info__reassure-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="#1B7FC4" stroke-width="2" width="18" height="18"><path d="M3 9 12 4l9 5v8l-9 5-9-5z"></path><path d="M3 9l9 5 9-5"></path></svg>
                Easy returns
            </div>
            <div class="jtc-pd-info__reassure-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="#1B7FC4" stroke-width="2" width="18" height="18"><rect x="3" y="5" width="18" height="14" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Secure payment · COD
            </div>
        </div>

        {{-- Share --}}
        <div class="jtc-pd-share" wire:ignore x-data="{
            url: window.location.href.split('#')[0],
            text: @js($product['name']),
            copied: false,
            canNative: !!navigator.share,
            enc(v) { return encodeURIComponent(v); },
            open(href) { window.open(href, '_blank', 'noopener,width=600,height=560'); },
            native() { navigator.share({ title: this.text, text: this.text, url: this.url }).catch(() => {}); },
            copy() {
                const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1800); };
                if (navigator.clipboard) { navigator.clipboard.writeText(this.url).then(done); return; }
                const t = document.createElement('textarea'); t.value = this.url; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done();
            },
        }">
            <span class="jtc-pd-share__label">Share</span>
            <div class="jtc-pd-share__list">
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--fb" @click="open('https://www.facebook.com/sharer/sharer.php?u=' + enc(url))" aria-label="Share on Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M13.5 22v-8.2h2.8l.4-3.2h-3.2V8.5c0-.9.3-1.6 1.6-1.6h1.7V4.1c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3v3.2h2.8V22z"></path></svg>
                </button>
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--wa" @click="open('https://wa.me/?text=' + enc(text + ' ' + url))" aria-label="Share on WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.52 11.99c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.98-.14.16-.29.18-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.16 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"></path></svg>
                </button>
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--x" @click="open('https://twitter.com/intent/tweet?text=' + enc(text) + '&url=' + enc(url))" aria-label="Share on X">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M17.75 3h3.07l-6.7 7.66L22 21h-6.17l-4.83-6.32L5.47 21H2.4l7.17-8.2L2 3h6.33l4.37 5.77zm-1.08 16.18h1.7L7.4 4.73H5.58z"></path></svg>
                </button>
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--tg" @click="open('https://t.me/share/url?url=' + enc(url) + '&text=' + enc(text))" aria-label="Share on Telegram">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.6-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.5 13.1l-4.7-1.5c-1-.3-1-1 .2-1.5L20.5 3c.9-.3 1.6.2 1.4 1.3z"></path></svg>
                </button>
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--copy" :class="copied && 'is-copied'" @click="copy()" aria-label="Copy link">
                    <svg x-show="!copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <svg x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M20 6 9 17l-5-5"></path></svg>
                </button>
                <button type="button" class="jtc-pd-share__btn jtc-pd-share__btn--more" x-show="canNative" x-cloak @click="native()" aria-label="More share options">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"></path></svg>
                </button>
            </div>
            <span class="jtc-pd-share__toast" x-show="copied" x-cloak x-transition.opacity>Link copied</span>
        </div>
    </div>

    {{-- Full description --}}
    @if($product['desc'])
        <div class="jtc-pd-desc">{!! $product['desc'] !!}</div>
    @endif

    {{-- Size guide modal --}}
    <div class="jtc-modal-scrim {{ $showSizeGuide ? 'is-open' : '' }}" wire:click.self="toggleSizeGuide">
        <div class="jtc-modal">
            <div class="jtc-modal__head">
                <h3>Size guide</h3>
                <button type="button" class="jtc-modal__close" wire:click="toggleSizeGuide">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
                </button>
            </div>
            <div class="jtc-form">
                <div class="jtc-sizeguide__table">
                    <span class="head">Size</span>
                    <span class="head">Chest (cm)</span>
                    @foreach([['XS','82'],['S','87'],['M','92'],['L','97'],['XL','102']] as [$sz, $ch])
                        <span>{{ $sz }}</span><span>{{ $ch }}</span>
                    @endforeach
                </div>
                <p style="font-size:0.85rem;color:#6b7a73;line-height:1.7">Model is 178 cm and wears size S. Fits true to size — size down between sizes for a closer fit.</p>
            </div>
        </div>
    </div>
</div>
