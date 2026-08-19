<div class="container section" style="margin-top:0"
    x-data="{
        media: {{ Illuminate\Support\Js::from($media) }},
        colors: {{ Illuminate\Support\Js::from($colors) }},
        hasRealVariants: {{ Illuminate\Support\Js::from($hasRealVariants) }},
        variantMatrix: {{ Illuminate\Support\Js::from($variantMatrix) }},
        productId: {{ Illuminate\Support\Js::from($productId) }},
        active:0, color:0, size:null, spot:false, gallery:false, guide:false, review:false, reviewSent:false, rating:0, alert:false,
        u(id,w){ return '{{ config('ecomx-fashion.unsplash') }}'+id+'?q=80&w='+w+'&auto=format&fit=crop'; },
        get cur(){ return this.media[this.active]; },
        pickColor(i){ this.color=i; let idx=this.media.findIndex(m=>m.img===this.colors[i].main); this.active=idx>=0?idx:0; },
        get selectedVariantId(){
            if (!this.hasRealVariants) return null;
            let colorKey = this.colors.length ? (this.colors[this.color]?.name ?? '*') : '*';
            let sizeKey = this.size ?? '*';
            return this.variantMatrix[colorKey + '|' + sizeKey]?.variantId ?? null;
        },
        addToCart(){
            if(!this.size){ this.spot=true; setTimeout(()=>this.spot=false,4000); this.$refs.sizeBox.scrollIntoView({behavior:'smooth',block:'center'}); return; }
            if (!this.productId) { this.added=true; return; }
            $dispatch('add-to-cart', { productId: this.productId, variantId: this.selectedVariantId });
            this.added=true;
        },
        added:false,
    }">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);padding:20px 0 16px"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <a href="{{ route('ecomx-fashion.category') }}" style="color:rgba(var(--pri-rgb),.5)">{{ $product['cat'] }}</a> / <span style="color:var(--pri)">{{ $product['name'] }}</span></nav>

    <div class="pdp">
        {{-- GALLERY --}}
        <div class="gallery">
            <div class="gallery__thumbs no-scrollbar">
                <template x-for="(m,i) in media" :key="'t'+i">
                    <button class="gallery__thumb" :class="active===i && 'is-active'" @click="active=i">
                        <img :src="u(m.img,200)" alt="" style="width:100%;height:100%;object-fit:cover">
                        <template x-if="m.video"><span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(var(--pri-rgb),.3);color:var(--sec);font-size:14px">▶</span></template>
                    </button>
                </template>
            </div>
            <div class="gallery__main" @click="gallery=true">
                <img :src="u(cur.img,1200)" :alt="'{{ $product['name'] }}'">
                <span style="position:absolute;top:14px;right:14px;background:var(--sec);color:var(--pri);font-size:10.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:6px 11px;border-radius:999px;animation:floaty 2.6s ease-in-out infinite">New</span>
            </div>
        </div>

        {{-- INFO --}}
        <div class="pdp__info">
            <div>
                <p class="kicker">{{ $product['cat'] }} · Autumn 2026</p>
                <h1 style="font-size:clamp(28px,3vw,40px);margin-bottom:10px">{{ $product['name'] }}</h1>
                @if($flashSale)
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        <span style="font-size:20px;font-weight:600;color:var(--ac2)">৳{{ number_format($product['sale']) }}</span>
                        <span class="price--old" style="font-size:15px">৳{{ number_format($product['price']) }}</span>
                    </div>
                    <div x-data="countdown()" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:12px;background:var(--pri);border-radius:12px;padding:11px 16px">
                        <span class="flash__badge" style="font-size:10.5px;padding:6px 12px">⚡ Flash Sale</span>
                        <span style="font-size:12px;color:rgba(var(--sec-rgb),.65)">Ends in</span>
                        <span class="flash__timer" style="display:flex;gap:5px"><span x-text="h" style="padding:4px 8px;font-size:14px"></span><span style="color:var(--ac3)">:</span><span x-text="m" style="padding:4px 8px;font-size:14px"></span><span style="color:var(--ac3)">:</span><span x-text="s" style="padding:4px 8px;font-size:14px"></span></span>
                    </div>
                @else
                    <span style="font-size:20px;font-weight:500">৳{{ number_format($product['price']) }}</span>
                @endif
            </div>

            <p style="font-size:13.5px;line-height:1.7;color:rgba(var(--pri-rgb),.7)">{{ $product['desc'] }}</p>

            {{-- Colours --}}
            <div>
                <div style="font-size:12.5px;font-weight:600;margin-bottom:10px">Colour — <span x-text="colors[color].name"></span></div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <template x-for="(c,i) in colors" :key="i">
                        <button class="swatch-sq" :class="color===i && 'is-on'" :style="c.hex2 ? 'background:linear-gradient(135deg,'+c.hex+' 50%,'+c.hex2+' 50%)' : 'background:'+c.hex" @click="pickColor(i)" :aria-label="c.name">
                            <template x-if="color===i"><span style="position:absolute;right:3px;bottom:3px;width:16px;height:16px;border-radius:999px;background:var(--ac);color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center">✓</span></template>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Sizes with tutorial spotlight --}}
            <div x-ref="sizeBox" :style="spot && 'position:relative;z-index:295;background:var(--sec);border-radius:14px;padding:14px;margin:-14px;box-shadow:0 0 0 3px var(--ac),0 0 0 9999px rgba(var(--pri-rgb),.55)'">
                <template x-if="spot"><div style="position:absolute;top:-38px;left:0;background:var(--ac);color:#fff;font-size:12px;font-weight:600;padding:8px 14px;border-radius:999px;animation:floaty 1.2s ease-in-out infinite;white-space:nowrap">👇 Choose your size first</div></template>
                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px">
                    <span style="font-size:12.5px;font-weight:600">Size<span x-show="size" x-text="' — '+size"></span></span>
                    <button @click="guide=true" style="border:none;background:none;font-size:12px;color:var(--ac2);text-decoration:underline">Size guide</button>
                </div>
                <div class="size-grid">
                    @foreach($sizes as $z)
                        <button class="size-btn size-btn--outline" :class="size==='{{ $z }}' && 'is-on'" @click="size='{{ $z }}';spot=false" @if($z==='XL') disabled @endif>
                            {{ $z }}
                            <template x-if="size==='{{ $z }}'"><span class="size-btn__check">✓</span></template>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Buy actions --}}
            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;gap:10px">
                    <button class="btn btn--primary" style="flex:1" @click="addToCart()" x-text="added ? 'Added to cart ✓' : 'Add to Cart — ৳{{ number_format($flashSale ? $product['sale'] : $product['price']) }}'"></button>
                    @if ($productId)
                        <button class="icon-btn {{ $this->isWished ? 'is-on' : '' }}" style="width:54px;height:auto;border-radius:12px;background:#fff;{{ $this->isWished ? 'color:var(--ac)' : '' }}" wire:click="toggleWishlist({{ $productId }})" wire:loading.attr="disabled" wire:target="toggleWishlist({{ $productId }})" aria-label="Wishlist"><x-icon name="heart" /></button>
                    @else
                        <button class="icon-btn" style="width:54px;height:auto;border-radius:12px;background:#fff" disabled aria-label="Wishlist"><x-icon name="heart" /></button>
                    @endif
                </div>
                <div style="display:flex;gap:10px">
                    <button class="btn btn--outline" style="flex:1;border-color:var(--ac);color:var(--ac2)" @click="size ? window.scrollTo(0,0) : (spot=true,setTimeout(()=>spot=false,4000))">Buy Now — 1 qty</button>
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

            {{-- Accordion: Specifications + details --}}
            <div style="border-top:1px solid rgba(var(--pri-rgb),.08)" x-data="{ open:0 }">
                <div style="border-bottom:1px solid rgba(var(--pri-rgb),.08)">
                    <button class="accordion__head" @click="open = open===0 ? -1 : 0">Specifications <span x-text="open===0?'−':'+'" style="color:rgba(var(--pri-rgb),.45)"></span></button>
                    <div x-show="open===0" x-collapse style="padding:0 2px 18px">
                        @foreach($specs as [$k,$v])
                            <div style="display:flex;justify-content:space-between;gap:16px;padding:9px 0;border-bottom:1px solid rgba(var(--pri-rgb),.06)"><span class="muted" style="font-size:12.5px">{{ $k }}</span><span style="font-size:12.5px;font-weight:500;text-align:right">{{ $v }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div style="border-bottom:1px solid rgba(var(--pri-rgb),.08)">
                    <button class="accordion__head" @click="open = open===1 ? -1 : 1">Shipping &amp; returns <span x-text="open===1?'−':'+'" style="color:rgba(var(--pri-rgb),.45)"></span></button>
                    <p x-show="open===1" x-collapse style="margin:0;padding:0 2px 18px;font-size:13px;line-height:1.75;color:rgba(var(--pri-rgb),.65)">Free delivery on orders over ৳5,000 — inside Dhaka in 24–48 hours, nationwide in 3–5 days. bKash, Nagad, card and cash on delivery accepted. 30-day returns, free of charge.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- REVIEWS --}}
    <section class="section reveal" aria-label="Reviews" style="margin-top:clamp(56px,8vw,96px)">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:20px;flex-wrap:wrap">
            <div><p class="kicker">★ 4.8 · 2,314 reviews</p><h2 class="h-section">Loved by our customers</h2></div>
            <button class="btn btn--primary btn--pill" style="padding:11px 20px" @click="review=true">✎ Write a review</button>
        </div>
        <x-review-slider :reviews="$reviews" />
    </section>

    {{-- RELATED carousel --}}
    <section class="section reveal" aria-label="Related products" style="margin-top:clamp(48px,7vw,80px)">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:22px">
            <h2 style="font-size:clamp(24px,2.6vw,32px)">You may also like</h2>
            <div style="display:flex;gap:8px">
                <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:-600,behavior:'smooth'})">←</button>
                <button class="icon-btn" onclick="this.closest('section').querySelector('[data-row]').scrollBy({left:600,behavior:'smooth'})">→</button>
            </div>
        </div>
        <div class="row-scroll no-scrollbar" data-row style="gap:18px">
            @foreach($related as $p)
                <div wire:key="related-{{ $p['id'] ?? $loop->index }}" style="flex:none;width:calc((100% - 3.3*18px)/4.3);min-width:200px"><x-product-card :product="$p" /></div>
            @endforeach
        </div>
    </section>

    {{-- Size guide modal --}}
    <template x-if="guide">
        <div class="modal" @click.self="guide=false">
            <div class="modal__box modal__box--lg">
                <div class="modal__head"><p class="modal__title" style="font-size:28px">Size guide</p><button class="modal__close" @click="guide=false">✕</button></div>
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
    </template>

    {{-- Write review modal --}}
    <template x-if="review">
        <div class="modal" @click.self="review=false">
            <div class="modal__box modal__box--md">
                <div class="modal__head"><p class="modal__title">Write a review</p><button class="modal__close" @click="review=false">✕</button></div>
                <template x-if="!reviewSent">
                    <div>
                        <p class="muted" style="font-size:13px;margin-bottom:20px">{{ $product['name'] }} · your honest thoughts help others</p>
                        <div class="field">
                            <label>Your rating</label>
                            <div style="display:flex;gap:6px">
                                <template x-for="n in 5" :key="n"><button @click="rating=n" style="border:none;background:none;font-size:30px;line-height:1;padding:0" :style="'color:'+(n<=rating?'var(--ac)':'rgba(var(--pri-rgb),.2)')">★</button></template>
                            </div>
                        </div>
                        <div class="field"><label>Your review</label><textarea rows="4" placeholder="How's the fit, fabric, delivery?" style="width:100%;padding:12px 14px;border:1px solid rgba(var(--pri-rgb),.15);border-radius:10px;background:#fff;font-size:13px;resize:vertical"></textarea></div>
                        <button class="btn btn--primary btn--block" @click="reviewSent=true">Submit review</button>
                    </div>
                </template>
                <template x-if="reviewSent">
                    <div style="text-align:center;padding:20px 0 8px">
                        <div style="width:60px;height:60px;border-radius:999px;background:var(--ac);color:#fff;font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">✓</div>
                        <p style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:6px">Thank you!</p>
                        <p class="muted" style="font-size:13px">Your review has been submitted for verification.</p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Fullscreen gallery --}}
    <template x-if="gallery">
        <div class="modal" style="background:rgba(var(--pri-rgb),.94)" @click.self="gallery=false">
            <button class="modal__close" style="position:absolute;top:18px;right:18px;color:#fff;border-color:rgba(255,255,255,.3)" @click="gallery=false">✕</button>
            <button class="icon-btn" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);background:none;color:#fff;border-color:rgba(255,255,255,.3)" @click="active=(active-1+media.length)%media.length">←</button>
            <button class="icon-btn" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);background:none;color:#fff;border-color:rgba(255,255,255,.3)" @click="active=(active+1)%media.length">→</button>
            <figure style="margin:0;max-width:min(90vw,560px);width:100%" @click.stop>
                <div style="position:relative;border-radius:14px;overflow:hidden;aspect-ratio:3/4;max-height:84vh;background:#26251F"><img :src="u(cur.img,1400)" alt="" style="width:100%;height:100%;object-fit:cover"></div>
            </figure>
        </div>
    </template>
</div>
