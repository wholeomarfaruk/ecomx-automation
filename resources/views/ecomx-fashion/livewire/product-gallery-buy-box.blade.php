<div>
<div class="pdp">
    {{-- GALLERY --}}
    <div class="gallery">
        <div class="gallery__thumbs no-scrollbar" x-data="gallerySwiperThumbs({{ $activeIndex }})" wire:key="gallery-thumbs-{{ md5(json_encode($media)) }}">
            @foreach($media as $i => $m)
                <button type="button" class="gallery__thumb" :class="active === {{ $i }} && 'is-active'" @click="goto({{ $i }})">
                    <img src="{{ $m['img'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
                    @if($m['video'])
                        <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(var(--pri-rgb),.3);color:var(--sec);font-size:14px">▶</span>
                    @endif
                </button>
            @endforeach
        </div>
        <div class="gallery__main swiper" x-data="gallerySwiper" x-init="init({{ $activeIndex }})" wire:key="gallery-main-{{ md5(json_encode($media)) }}" wire:ignore>
            <div class="swiper-wrapper">
                @foreach($media as $i => $m)
                    <a class="swiper-slide" href="{{ $m['img'] }}" data-fancybox="pdp-gallery" @if($m['video']) data-type="html5video" @endif>
                        <img src="{{ $m['img'] }}" alt="{{ $product['name'] }}">
                        @if($m['video'])
                            <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(var(--pri-rgb),.25)"><span style="width:52px;height:52px;border-radius:999px;background:rgba(var(--sec-rgb),.92);display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--pri)">▶</span></span>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="swiper-button-prev" x-show="slideCount > 1"></div>
            <div class="swiper-button-next" x-show="slideCount > 1"></div>
            <span style="position:absolute;top:14px;right:14px;background:var(--sec);color:var(--pri);font-size:10.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:6px 11px;border-radius:999px;animation:floaty 2.6s ease-in-out infinite;z-index:2">New</span>
        </div>
    </div>

    {{-- INFO --}}
    <div class="pdp__info">
        <div>
            <p class="kicker">{{ $product['cat'] }} · Autumn 2026</p>
            <h1 style="font-size:clamp(28px,3vw,40px);margin-bottom:10px">{{ $product['name'] }}</h1>
            @if($this->currentComparePrice !== null)
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    <span style="font-size:20px;font-weight:600;color:var(--ac2)">৳{{ number_format($this->currentPrice) }}</span>
                    <span class="price--old" style="font-size:15px">৳{{ number_format($this->currentComparePrice) }}</span>
                </div>
                @if($flashSale)
                <div x-data="countdown()" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:12px;background:var(--pri);border-radius:12px;padding:11px 16px">
                    <span class="flash__badge" style="font-size:10.5px;padding:6px 12px">⚡ Flash Sale</span>
                    <span style="font-size:12px;color:rgba(var(--sec-rgb),.65)">Ends in</span>
                    <span class="flash__timer" style="display:flex;gap:5px"><span x-text="h" style="padding:4px 8px;font-size:14px"></span><span style="color:var(--ac3)">:</span><span x-text="m" style="padding:4px 8px;font-size:14px"></span><span style="color:var(--ac3)">:</span><span x-text="s" style="padding:4px 8px;font-size:14px"></span></span>
                </div>
                @endif
            @else
                <span style="font-size:20px;font-weight:500">৳{{ number_format($this->currentPrice) }}</span>
            @endif
        </div>

        <p style="font-size:13.5px;line-height:1.7;color:rgba(var(--pri-rgb),.7)">{{ $product['desc'] }}</p>

        @if($hasColors)
        {{-- Colours --}}
        <div>
            <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Colour — {{ $colors[$selectedColorIndex]['name'] ?? '' }}</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                @foreach($colors as $i => $c)
                    <button type="button" class="swatch-sq {{ $selectedColorIndex === $i ? 'is-on' : '' }}" @if(empty($c['image'])) style="{{ !empty($c['hex2']) ? 'background:linear-gradient(135deg,'.$c['hex'].' 50%,'.$c['hex2'].' 50%)' : 'background:'.$c['hex'] }}" @endif wire:click="selectColor({{ $i }})" aria-label="{{ $c['name'] }}">
                        @if(!empty($c['image']))
                            <img src="{{ $c['image'] }}" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
                        @endif
                        @if($selectedColorIndex === $i)
                            <span style="position:absolute;right:3px;bottom:3px;width:16px;height:16px;border-radius:999px;background:var(--ac);color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center">✓</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        @if($hasSizes)
        {{-- Sizes with tutorial spotlight --}}
        <div class="{{ $showSizePrompt ? 'size-box--spot' : '' }}" style="position:relative">
            @if($showSizePrompt)
                <div style="position:absolute;top:-38px;left:0;background:var(--ac);color:#fff;font-size:12px;font-weight:600;padding:8px 14px;border-radius:999px;animation:floaty 1.2s ease-in-out infinite;white-space:nowrap">👇 Choose your size first</div>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px">
                <span style="font-size:12.5px;font-weight:600">Size @if($selectedSize) — {{ $selectedSize }} @endif</span>
                <button type="button" wire:click="toggleSizeGuide" style="border:none;background:none;font-size:12px;color:var(--ac2);text-decoration:underline">Size guide</button>
            </div>
            <div class="size-grid">
                @foreach($sizes as $z)
                    <button type="button" class="size-btn size-btn--outline {{ $selectedSize === $z ? 'is-on' : '' }}" @if($this->isSizeOutOfStock($z)) disabled @endif wire:click="selectSize('{{ $z }}')">
                        {{ $z }}
                        @if($selectedSize === $z)
                            <span class="size-btn__check">✓</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Buy actions --}}
        <div style="display:flex;flex-direction:column;gap:10px">
            <div style="display:flex;gap:10px">
                <button type="button" class="btn btn--primary" style="flex:1" wire:click="addToCart">{{ $addedToCart ? 'Added to cart ✓' : 'Add to Cart — ৳' . number_format($this->currentPrice) }}</button>
                <button class="icon-btn {{ $this->isWished ? 'is-on' : '' }}" style="width:54px;height:auto;border-radius:12px;background:#fff;{{ $this->isWished ? 'color:var(--ac)' : '' }}" wire:click="toggleWishlist({{ $productId }})" wire:loading.attr="disabled" wire:target="toggleWishlist({{ $productId }})" aria-label="Wishlist"><x-icon name="heart" /></button>
            </div>
            <div style="display:flex;gap:10px">
                <button type="button" class="btn btn--outline" style="flex:1;border-color:var(--ac);color:var(--ac2)" wire:click="addToCart">Buy Now — 1 qty</button>
                <a href="https://wa.me/8801700000000" target="_blank" rel="noopener" class="icon-btn" style="width:54px;height:auto;border-radius:12px;border:1.5px solid #25D366;background:rgba(37,211,102,.08);color:#128C4A" aria-label="Order via WhatsApp"><x-icon name="whatsapp" /></a>
            </div>
        </div>

        <div style="display:flex;gap:18px;flex-wrap:wrap" class="muted">
            <span style="font-size:12px">◈ Free delivery over ৳5,000</span>
            <span style="font-size:12px">↺ 30-day returns · COD available</span>
            <span style="font-size:12px">✦ Made in Bangladesh</span>
        </div>

        {{-- Delivery timeline --}}
        <div class="timeline">
            @php $tl = [['M4 12.5l5 5L20 6.5','Purchase','Today','on'],['M21 8V21H3V8M1 3h22v5H1zM10 12h4','Processing','Tomorrow','mid'],['M1 3h15v13H1zM16 8h4l3 3v5h-7V8zM5.5 21a2 2 0 100-4 2 2 0 000 4zM18.5 21a2 2 0 100-4 2 2 0 000 4z','Delivery','In 3 days','off']]; @endphp
            @foreach($tl as $i => [$path,$label,$sub,$state])
                <div class="timeline__step">
                    @if(!$loop->last)<span class="timeline__bar" style="background:{{ $state==='on' ? 'var(--pri)' : 'rgba(var(--pri-rgb),.15)' }}"></span>@endif
                    <span class="timeline__dot" style="background:{{ $state==='on'?'var(--pri)':'var(--sec)' }};color:{{ $state==='on'?'var(--sec)':($state==='mid'?'var(--ac2)':'rgba(var(--pri-rgb),.55)') }};border:1.5px solid {{ $state==='on'?'var(--pri)':($state==='mid'?'var(--ac)':'rgba(var(--pri-rgb),.2)') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                    </span>
                    <span style="font-size:11.5px;font-weight:600">{{ $label }} <span class="muted" style="font-weight:400">· {{ $sub }}</span></span>
                </div>
            @endforeach
        </div>

        {{-- Accordion: Shipping & returns --}}
        <div style="border-top:1px solid rgba(var(--pri-rgb),.08)" x-data="{ open:0 }">
            <div style="border-bottom:1px solid rgba(var(--pri-rgb),.08)">
                <button class="accordion__head" @click="open = open===0 ? -1 : 0">Specifications <span x-text="open===0?'−':'+'" style="color:rgba(var(--pri-rgb),.45)"></span></button>
                <p x-show="open===0" x-collapse style="margin:0;padding:0 2px 18px;font-size:13px;line-height:1.75;color:rgba(var(--pri-rgb),.65)">{!! $product['desc'] !!}</p>
            </div>
            <div style="border-bottom:1px solid rgba(var(--pri-rgb),.08)">
                <button class="accordion__head" @click="open = open===0 ? -1 : 0">Shipping &amp; returns <span x-text="open===0?'−':'+'" style="color:rgba(var(--pri-rgb),.45)"></span></button>
                <p x-show="open===0" x-collapse style="margin:0;padding:0 2px 18px;font-size:13px;line-height:1.75;color:rgba(var(--pri-rgb),.65)">Free delivery on orders over ৳5,000 — inside Dhaka in 24–48 hours, nationwide in 3–5 days. bKash, Nagad, card and cash on delivery accepted. 30-day returns, free of charge.</p>
            </div>
        </div>
    </div>

    {{-- Size guide modal --}}
    @if($showSizeGuide)
        <div class="modal" wire:click.self="toggleSizeGuide">
            <div class="modal__box modal__box--lg">
                <div class="modal__head"><p class="modal__title" style="font-size:28px">Size guide</p><button type="button" class="modal__close" wire:click="toggleSizeGuide">✕</button></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:rgba(var(--pri-rgb),.1);border:1px solid rgba(var(--pri-rgb),.1);border-radius:10px;overflow:hidden;margin-bottom:16px">
                    <span style="background:var(--pri);color:var(--sec);padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase">Size</span>
                    <span style="background:var(--pri);color:var(--sec);padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase">Chest (cm)</span>
                    @foreach([['XS','82'],['S','87'],['M','92'],['L','97'],['XL','102']] as [$sz,$ch])
                        <span style="background:#fff;padding:13px 16px;font-size:14px;font-weight:600">{{ $sz }}</span><span style="background:#fff;padding:13px 16px;font-size:14px;color:rgba(var(--pri-rgb),.7)">{{ $ch }}</span>
                    @endforeach
                </div>
                <p class="muted" style="font-size:13.5px;line-height:1.7">Model is 178 cm and wears size S. Fits true to size — size down between sizes for a closer fit.</p>
            </div>
        </div>
    @endif
</div>
</div>
