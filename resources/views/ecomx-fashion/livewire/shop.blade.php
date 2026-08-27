<div class="container section" style="margin-top:0">
    <nav aria-label="Breadcrumb" style="font-size:12px;color:rgba(var(--pri-rgb),.5);padding:20px 0 0"><a href="{{ route('ecomx-fashion.home') }}" style="color:rgba(var(--pri-rgb),.5)">Home</a> / <span style="color:var(--pri)">Shop all</span></nav>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;padding:12px 0 24px">
        <div><p class="muted" style="font-size:13px;margin-top:6px">{{ number_format($total) }} pieces · considered, rarely restocked</p></div>
        <div style="display:flex;gap:10px;align-items:center" x-data="{ sheet:false }">
            <div class="cat-search">
                <input type="search" wire:model.live.debounce.400ms="q" placeholder="Search products" aria-label="Search products">
                <x-icon name="search" />
            </div>

            <button type="button" class="btn btn--outline btn--pill filters-toggle" style="padding:11px 18px" @click="sheet=true">
                ⚙ Filters
                @if(count($cats) || count($sizes) || count($offers) || $q !== '')
                    <span class="filters-toggle__count">{{ count($cats) + count($sizes) + count($offers) + ($q !== '' ? 1 : 0) }}</span>
                @endif
            </button>
            <div style="display:flex;align-items:center;gap:8px;border:1px solid rgba(var(--pri-rgb),.15);background:#fff;border-radius:999px;padding:6px 8px 6px 16px">
                <span class="muted" style="font-size:12px">Sort</span>
                <select wire:model.live="sort" style="border:none;background:none;font-size:12.5px;font-weight:500;outline:none;padding:5px 2px">
                    <option>Featured</option><option>Newest</option><option>Price: low to high</option><option>Price: high to low</option>
                </select>
            </div>

            {{-- Mobile filter sheet --}}
            <template x-if="sheet">
                <div class="overlay" style="z-index:210" @click.self="sheet=false" @keydown.escape.window="sheet=false">
                    <div class="sheet" role="dialog" aria-modal="true" aria-label="Filters">
                        <div class="sheet__head">
                            <span class="sheet__title">Filters</span>
                            <button type="button" class="modal__close" @click="sheet=false" aria-label="Close filters">✕</button>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:24px">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <span class="muted" style="font-size:12px">{{ number_format($total) }} results</span>
                                <button type="button" wire:click="clearAll" style="border:none;background:none;font-size:12px;color:var(--ac2)">Clear all</button>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Search</span>
                                <input type="search" wire:model.live.debounce.400ms="q" placeholder="Search products"
                                       style="width:100%;border:1px solid rgba(var(--pri-rgb),.15);background:#fff;border-radius:10px;padding:11px 14px;font-size:13px">
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Category</span>
                                @foreach($allCats as $c)
                                    <button type="button" class="filter-check" wire:click="toggleCat('{{ $c }}')">
                                        <span style="display:flex;align-items:center;gap:10px"><span class="filter-box {{ in_array($c,$cats)?'is-on':'' }}">{{ in_array($c,$cats)?'✓':'' }}</span>{{ $c }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Offers</span>
                                @foreach($allOffers as $key => $label)
                                    <button type="button" class="filter-check" wire:click="toggleOffer('{{ $key }}')">
                                        <span style="display:flex;align-items:center;gap:10px"><span class="filter-box {{ in_array($key,$offers)?'is-on':'' }}">{{ in_array($key,$offers)?'✓':'' }}</span>{{ $label }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Size</span>
                                <div style="display:flex;flex-wrap:wrap;gap:8px">
                                    @foreach($allSizes as $z)
                                        <button type="button" class="size-btn {{ in_array($z,$sizes)?'is-on':'' }}" style="min-width:42px;padding:9px 12px" wire:click="toggleSize('{{ $z }}')">{{ $z }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Price</span>
                                <div x-data="{ live: {{ $maxPrice }} }">
                                    <input type="range" min="1000" max="15000" step="500" x-model.number="live" wire:model.live.debounce.400ms="maxPrice" style="width:100%;accent-color:var(--ac)">
                                    <div style="display:flex;justify-content:space-between;font-size:12px" class="muted"><span>৳1,000</span><span style="font-weight:600;color:var(--pri)" x-text="'Up to ৳' + live.toLocaleString('en-US')"></span></div>
                                </div>
                            </div>
                        </div>

                        <div class="sheet__foot">
                            <button type="button" class="btn btn--primary btn--block" @click="sheet=false">Show {{ number_format($total) }} results</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="shop-layout">
        <aside class="filters" aria-label="Filters">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase">Filters</span>
                <button type="button" wire:click="clearAll" style="border:none;background:none;font-size:12px;color:var(--ac2)">Clear all</button>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Category</span>
                @foreach($allCats as $c)
                    <button type="button" class="filter-check" wire:click="toggleCat('{{ $c }}')">
                        <span style="display:flex;align-items:center;gap:10px"><span class="filter-box {{ in_array($c,$cats)?'is-on':'' }}">{{ in_array($c,$cats)?'✓':'' }}</span>{{ $c }}</span>
                    </button>
                @endforeach
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Offers</span>
                @foreach($allOffers as $key => $label)
                    <button type="button" class="filter-check" wire:click="toggleOffer('{{ $key }}')">
                        <span style="display:flex;align-items:center;gap:10px"><span class="filter-box {{ in_array($key,$offers)?'is-on':'' }}">{{ in_array($key,$offers)?'✓':'' }}</span>{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Size</span>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach($allSizes as $z)
                        <button type="button" class="size-btn {{ in_array($z,$sizes)?'is-on':'' }}" style="min-width:42px;padding:9px 12px" wire:click="toggleSize('{{ $z }}')">{{ $z }}</button>
                    @endforeach
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <span class="kicker" style="color:rgba(var(--pri-rgb),.45)">Price</span>
                <div x-data="{ live: {{ $maxPrice }} }">
                    <input type="range" min="1000" max="15000" step="500" x-model.number="live" wire:model.live.debounce.400ms="maxPrice" style="width:100%;accent-color:var(--ac)">
                    <div style="display:flex;justify-content:space-between;font-size:12px" class="muted"><span>৳1,000</span><span style="font-weight:600;color:var(--pri)" x-text="'Up to ৳' + live.toLocaleString('en-US')"></span></div>
                </div>
            </div>
        </aside>

        <div>
            @if(count($cats) || count($sizes) || count($offers))
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px">
                    @foreach($cats as $c)<button class="chip" wire:click="toggleCat('{{ $c }}')">{{ $c }} ✕</button>@endforeach
                    @foreach($sizes as $z)<button class="chip" wire:click="toggleSize('{{ $z }}')">Size {{ $z }} ✕</button>@endforeach
                    @foreach($offers as $o)<button class="chip" wire:click="toggleOffer('{{ $o }}')">{{ $allOffers[$o] ?? $o }} ✕</button>@endforeach
                </div>
            @endif
            <div wire:loading.remove wire:target="toggleCat,toggleSize,toggleOffer,clearAll,sort,maxPrice,q">
                @if(count($items))
                    <div class="grid-auto">
                        @foreach($items as $p)<x-product-card :product="$p" wire:key="p-{{ $p['id'] }}" />@endforeach
                    </div>

                    @if($hasMore)
                        <div style="display:flex;flex-direction:column;align-items:center;gap:8px;margin-top:32px">
                            <button class="btn btn--outline btn--pill" style="padding:12px 28px"
                                    wire:click="loadMore" wire:loading.attr="disabled" wire:loading.class="is-loading" wire:target="loadMore">
                                <span class="btn__label">Load more</span>
                                <span class="btn__loading"><span class="spinner"></span> Loading…</span>
                            </button>
                            <span class="muted" style="font-size:12px">Showing {{ number_format(count($items)) }} of {{ number_format($total) }}</span>
                        </div>
                    @endif
                @else
                    <div style="text-align:center;padding:80px 20px">
                        <p style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:8px">Nothing matches — yet.</p>
                        <button class="btn btn--outline btn--pill" wire:click="clearAll">Clear filters</button>
                    </div>
                @endif
            </div>

            <div wire:loading wire:target="toggleCat,toggleSize,toggleOffer,clearAll,sort,maxPrice,q" class="grid-auto">
                @foreach(range(1, 8) as $skeletonIndex)
                    <div class="skel" style="aspect-ratio:3/4;border-radius:var(--radius)"></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
