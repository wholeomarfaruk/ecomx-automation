@props(['reviews'])
<div x-data="{
        reviews: {{ Illuminate\Support\Js::from($reviews) }},
        box: null,
        u(id,w){
            if (!id) return '{{ asset('images/placeholder.jpg') }}';
            if (/^https?:\/\//.test(id) || id.startsWith('/')) return id;
            return '{{ config('ecomx-fashion.unsplash') }}'+id+'?q=80&w='+w+'&auto=format&fit=crop';
        },
        avatarUrl(id,w){
            if (!id) return '{{ asset('icons/avater-icon.jpg') }}';
            return this.u(id,w);
        },
        stars(n){ return '★'.repeat(n)+'☆'.repeat(5-n); },
        scroll(d){ this.$refs.row.scrollBy({left:d*320,behavior:'smooth'}); },
        get item(){ return this.box!==null ? this.reviews[this.box] : null; },
        prev(){ this.box=(this.box-1+this.reviews.length)%this.reviews.length; },
        next(){ this.box=(this.box+1)%this.reviews.length; },
    }">
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:14px">
        <button class="icon-btn" @click="scroll(-1)" aria-label="Previous reviews">←</button>
        <button class="icon-btn" @click="scroll(1)" aria-label="Next reviews">→</button>
    </div>
    <div class="row-scroll no-scrollbar" x-ref="row" style="gap:20px">
        <template x-for="(r,i) in reviews" :key="r.id">
            <article class="rcard">
                <div class="rcard__media" @click="box=i">
                    <img :src="u(r.img,600)" :alt="r.product">
                    <template x-if="r.video">
                        <span style="position:absolute;top:12px;right:12px;background:rgba(var(--pri-rgb),.65);color:var(--sec);font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:5px 10px;border-radius:999px">Video</span>
                    </template>
                    <div class="rcard__foot">
                        <img class="rcard__avatar" :src="avatarUrl(r.avatar,100)" :alt="r.name">
                        <div style="flex:1;min-width:0">
                            <span style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600">
                                <span x-text="r.name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></span>
                                <template x-if="r.verified"><span class="verified__tick">✓</span></template>
                            </span>
                        </div>
                        <span class="rcard__stars" x-text="stars(r.rating)"></span>
                    </div>
                </div>
            </article>
        </template>
    </div>

    {{-- Lightbox --}}
    <template x-if="box!==null">
        <div class="modal" style="background:rgba(var(--pri-rgb),.94);z-index:200" @click.self="box=null">
            <button class="modal__close" style="position:absolute;top:18px;right:18px;color:#fff;border-color:rgba(255,255,255,.3)" @click="box=null">✕</button>
            <button class="icon-btn" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);background:none;color:#fff;border-color:rgba(255,255,255,.3)" @click="prev()">←</button>
            <button class="icon-btn" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);background:none;color:#fff;border-color:rgba(255,255,255,.3)" @click="next()">→</button>
            <figure style="margin:0;max-width:min(86vw,480px);width:100%" @click.stop>
                <div style="position:relative;border-radius:14px;overflow:hidden;aspect-ratio:9/16;max-height:80vh;background:#26251F">
                    <img :src="u(item.img,900)" :alt="item.product" style="width:100%;height:100%;object-fit:cover">
                </div>
                <figcaption style="display:flex;align-items:center;gap:12px;color:#fff;margin-top:12px">
                    <img :src="avatarUrl(item.avatar,100)" style="width:36px;height:36px;border-radius:999px;object-fit:cover">
                    <div style="flex:1"><span x-text="item.name" style="font-size:13.5px;font-weight:600"></span><br><span x-text="'On: '+item.product" style="font-size:12px;opacity:.6"></span></div>
                    <span class="rcard__stars" x-text="stars(item.rating)"></span>
                </figcaption>
            </figure>
        </div>
    </template>
</div>
