<div x-data="{ mobileFiltersOpen: false }">
    {{-- Page head --}}
    <div class="jtc-shop__head">
        <div class="jtc-shop__crumb">
            <a href="{{ route('ecomx-anyniche.home') }}" style="color:inherit;text-decoration:none">Home</a>
            <span>/</span>
            <span style="color:#14201c;font-weight:600">Shop</span>
        </div>
        <div class="jtc-shop__headrow">
            <h1>Shop all products</h1>
            <span class="jtc-shop__count">{{ number_format($total) }} product{{ $total === 1 ? '' : 's' }} found</span>
        </div>
    </div>

    {{-- Body --}}
    <div class="jtc-shop__body">
        <aside class="jtc-filters">
            @include('ecomx-anyniche.partials.shop-filters')
        </aside>

        <div class="jtc-shop__main">
            {{-- toolbar --}}
            <div class="jtc-toolbar">
                <button type="button" class="jtc-toolbar__filterbtn" @click="mobileFiltersOpen = true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="4" y1="6" x2="20" y2="6"></line><line x1="7" y1="12" x2="17" y2="12"></line><line x1="10" y1="18" x2="14" y2="18"></line></svg>
                    Filters
                    @if(count($cats) || count($brands) || count($sizes) || count($offers) || $q !== '')
                        <span>{{ count($cats) + count($brands) + count($sizes) + count($offers) + ($q !== '' ? 1 : 0) }}</span>
                    @endif
                </button>
                <div class="jtc-toolbar__sort">
                    <span>Sort by</span>
                    <select wire:model.live="sort">
                        <option>Featured</option>
                        <option>Newest</option>
                        <option>Price: low to high</option>
                        <option>Price: high to low</option>
                    </select>
                </div>
            </div>

            @if(count($cats) || count($brands) || count($sizes) || count($offers))
                <div class="jtc-filterchips">
                    @foreach($cats as $c)<button type="button" class="jtc-filterchip" wire:click="toggleCat('{{ $c }}')">{{ $c }} ✕</button>@endforeach
                    @foreach($brands as $b)<button type="button" class="jtc-filterchip" wire:click="toggleBrand('{{ $b }}')">{{ $b }} ✕</button>@endforeach
                    @foreach($sizes as $z)<button type="button" class="jtc-filterchip" wire:click="toggleSize('{{ $z }}')">Size {{ $z }} ✕</button>@endforeach
                    @foreach($offers as $o)<button type="button" class="jtc-filterchip" wire:click="toggleOffer('{{ $o }}')">{{ $allOffers[$o] ?? $o }} ✕</button>@endforeach
                </div>
            @endif

            {{-- grid --}}
            <div wire:loading.remove wire:target="toggleCat,toggleBrand,toggleSize,toggleOffer,clearAll,sort,minPrice,maxPrice,q">
                @if(count($items) > 0)
                    <div class="jtc-shopgrid">
                        @foreach($items as $p)
                            <x-anyniche::jtc-product-card :product="$p" :rail="false" wire:key="shop-{{ $p['id'] }}" />
                        @endforeach
                    </div>

                    @if($hasMore)
                        <div class="jtc-pagination">
                            <button type="button" class="jtc-btn jtc-btn--outline" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                                Load more
                            </button>
                        </div>
                        <p style="text-align:center;font-size:0.82rem;color:#6b7a73;margin-top:10px">Showing {{ number_format(count($items)) }} of {{ number_format($total) }}</p>
                    @endif
                @else
                    <div class="jtc-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="56" height="56"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <p>No products found</p>
                        <p>Try clearing some filters.</p>
                        <button type="button" class="jtc-btn jtc-btn--outline" wire:click="clearAll">Clear filters</button>
                    </div>
                @endif
            </div>

            <div wire:loading wire:target="toggleCat,toggleBrand,toggleSize,toggleOffer,clearAll,sort,minPrice,maxPrice,q" class="jtc-shopgrid">
                @foreach(range(1, 8) as $skeletonIndex)
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
    </div>

    {{-- mobile filter drawer --}}
    <div class="jtc-scrim" :class="mobileFiltersOpen && 'is-open'" @click="mobileFiltersOpen = false" x-cloak></div>
    <aside class="jtc-fdrawer" :class="mobileFiltersOpen && 'is-open'" x-cloak>
        <div class="jtc-fdrawer__head">
            <h3>Filters</h3>
            <button type="button" class="jtc-fdrawer__close" aria-label="Close" @click="mobileFiltersOpen = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
        </div>
        <div class="jtc-fdrawer__body">
            @include('ecomx-anyniche.partials.shop-filters', ['bare' => true])
        </div>
        <div class="jtc-fdrawer__foot">
            <button type="button" class="jtc-btn jtc-btn--primary jtc-btn--block" @click="mobileFiltersOpen = false">Show {{ number_format($total) }} results</button>
        </div>
    </aside>
</div>
