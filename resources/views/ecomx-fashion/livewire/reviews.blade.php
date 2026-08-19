<div class="container section" style="margin-top:0">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);padding:20px 0 0"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span style="color:var(--pri)">Customer reviews</span></nav>
    <h1 style="font-size:clamp(30px,3.6vw,46px);margin:12px 0 6px">Loved by our customers</h1>
    <p class="muted" style="font-size:13.5px;margin-bottom:28px;max-width:520px">Every review is from a real order — photos and videos included, unedited.</p>

    <div class="rv-stats" style="margin-bottom:32px">
        <div class="rv-stat-dark">
            <div style="display:flex;align-items:baseline;gap:10px"><span style="font-family:'Playfair Display',serif;font-size:clamp(44px,5vw,64px);line-height:1">4.8</span><span class="rcard__stars">★★★★★</span></div>
            <p style="font-size:13px;color:rgba(var(--sec-rgb),.65)">2,314 verified reviews</p>
            <p style="font-size:12.5px;color:var(--ac3);font-weight:500">96% would recommend Seldom Fashion</p>
        </div>
        <div class="rv-card-lite" style="display:flex;flex-direction:column;justify-content:center;gap:9px">
            @foreach($dist as [$starN,$n])
                <button wire:click="setStar({{ $starN }})" style="display:grid;grid-template-columns:44px 1fr 44px;align-items:center;gap:12px;border:none;background:none;padding:2px 0">
                    <span style="font-size:12.5px;text-align:left;font-weight:{{ $star===$starN ? 600 : 400 }}">{{ $starN }} ★</span>
                    <span class="rv-bar"><span style="width:{{ round($n/1856*100) }}%;background:{{ $star===$starN ? 'var(--ac)' : 'var(--pri)' }}"></span></span>
                    <span class="muted" style="font-size:11.5px;text-align:right">{{ number_format($n) }}</span>
                </button>
            @endforeach
        </div>
        <div class="rv-card-lite" style="display:flex;flex-direction:column;justify-content:center;gap:16px">
            @foreach([['Fit','True to size','50%'],['Quality','Exceptional','88%'],['Comfort','Very comfortable','78%']] as [$lab,$note,$pos])
                <div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span style="font-size:12px;font-weight:600">{{ $lab }}</span><span class="muted" style="font-size:11.5px">{{ $note }}</span></div>
                    <div style="position:relative;height:6px;border-radius:999px;background:rgba(var(--pri-rgb),.07)"><span style="position:absolute;top:50%;left:{{ $pos }};transform:translate(-50%,-50%);width:14px;height:14px;border-radius:999px;background:var(--ac);border:2px solid var(--sec)"></span></div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:22px">
        <div style="display:flex;gap:8px;overflow-x:auto;padding:2px" class="no-scrollbar">
            @foreach(['All','With photos','With video','Verified'] as $fl)
                <button class="rv-filter {{ $filter===$fl ? 'is-active' : '' }}" wire:click="$set('filter','{{ $fl }}')">{{ $fl }}</button>
            @endforeach
        </div>
        <div style="display:flex;align-items:center;gap:8px;border:1px solid rgba(var(--pri-rgb),.15);background:#fff;border-radius:999px;padding:5px 8px 5px 14px">
            <span class="muted" style="font-size:12px">Sort</span>
            <select wire:model.live="sort" style="border:none;background:none;font-size:12.5px;font-weight:500;outline:none;padding:4px 2px"><option>Most helpful</option><option>Newest</option><option>Highest rated</option><option>Lowest rated</option></select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,320px),1fr));gap:18px"
        x-data="{ voted:{}, u(id,w){ return '{{ config('ecomx-fashion.unsplash') }}'+id+'?q=80&w='+w+'&auto=format&fit=crop'; } }">
        @forelse($items as $r)
            <article style="background:#fff;border:1px solid rgba(var(--pri-rgb),.05);border-radius:14px;overflow:hidden;box-shadow:0 10px 30px rgba(var(--pri-rgb),.05);display:flex;flex-direction:column" wire:key="rv-{{ $r['id'] }}">
                <div style="position:relative;aspect-ratio:16/10;background:#ECE9E3">
                    <x-ux-img :id="$r['img']" :w="700" :alt="$r['product']" style="width:100%;height:100%;object-fit:cover" />
                    @if($r['video'])<span style="position:absolute;top:10px;right:10px;background:rgba(var(--pri-rgb),.65);color:var(--sec);font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;padding:5px 10px;border-radius:999px">Video</span>@endif
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;padding:18px 20px;flex:1">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                        <span class="stars">{{ str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) }}</span>
                        <span class="muted" style="font-size:11.5px">{{ $r['date'] }}</span>
                    </div>
                    <p style="margin:0;font-size:13.5px;line-height:1.65;color:rgba(var(--pri-rgb),.78)">“{{ $r['text'] }}”</p>
                    <a href="{{ route('ecomx-fashion.product') }}" style="font-size:12px;color:var(--ac2);font-weight:500">On: {{ $r['product'] }} →</a>
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:auto;padding-top:8px;border-top:1px solid rgba(var(--pri-rgb),.06)">
                        <div style="display:flex;align-items:center;gap:9px;min-width:0">
                            <x-ux-img :id="$r['avatar']" :w="100" alt="" style="width:30px;height:30px;border-radius:999px;object-fit:cover;flex:none" />
                            <div style="min-width:0">
                                <span style="display:block;font-size:12px;font-weight:600">{{ $r['name'] }}</span>
                                @if($r['verified'])<span class="verified"><span class="verified__tick">✓</span>Verified purchase</span>@endif
                            </div>
                        </div>
                        <button @click="voted[{{ $r['id'] }}]=!voted[{{ $r['id'] }}]" style="flex:none;display:flex;align-items:center;gap:6px;border:1px solid rgba(var(--pri-rgb),.15);background:none;border-radius:999px;padding:7px 13px;font-size:11.5px" :style="voted[{{ $r['id'] }}] && 'background:var(--pri);color:var(--sec);border-color:var(--pri)'">↑ Helpful · <span x-text="{{ $r['helpful'] }} + (voted[{{ $r['id'] }}]?1:0)"></span></button>
                    </div>
                </div>
            </article>
        @empty
            <div style="grid-column:1/-1;text-align:center;padding:70px 20px">
                <p style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:10px">No reviews match those filters.</p>
                <button class="btn btn--outline btn--pill" wire:click="$set('filter','All')">Show all reviews</button>
            </div>
        @endforelse
    </div>
</div>
