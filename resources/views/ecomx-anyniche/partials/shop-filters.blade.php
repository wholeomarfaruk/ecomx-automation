@php
    $bare = $bare ?? false;
@endphp

<div @class(['jtc-filters__card' => ! $bare])>
    @unless($bare)
        <div class="jtc-filters__top">
            <h3>Filters</h3>
            <button type="button" class="jtc-filters__clear" wire:click="clearAll">Clear all</button>
        </div>
    @endunless

    {{-- Search --}}
    <div class="jtc-filters__search" @if($bare) style="margin-bottom:18px" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <input type="search" wire:model.live.debounce.400ms="q" placeholder="Search products…" aria-label="Search products">
    </div>

    {{-- Price --}}
    <div @class(['jtc-filters__group' => ! $bare])>
        <div class="jtc-filters__label">Price range</div>
        <div class="jtc-filters__price-inputs">
            <input type="number" min="0" wire:model.live.debounce.400ms="minPrice" placeholder="Min" aria-label="Minimum price">
            <span>–</span>
            <input type="number" min="0" wire:model.live.debounce.400ms="maxPrice" placeholder="Max" aria-label="Maximum price">
        </div>
        <input type="range" min="1000" max="15000" step="500" wire:model.live.debounce.400ms="maxPrice" style="width:100%">
        <div class="jtc-filters__price-scale">
            <span>৳1,000</span>
            <strong>Up to ৳{{ number_format($maxPrice) }}</strong>
        </div>
    </div>

    {{-- Category --}}
    <div @class(['jtc-filters__group' => ! $bare]) @if($bare) style="margin-top:18px" @endif>
        <div class="jtc-filters__label">Category</div>
        <div class="jtc-filters__list">
            @forelse($allCats as $c)
                <label class="jtc-filters__check">
                    <input type="checkbox" @checked(in_array($c['name'], $cats)) wire:click="toggleCat('{{ $c['name'] }}')">
                    {{ $c['name'] }} <span>({{ $c['count'] }})</span>
                </label>
            @empty
                <span class="jtc-filters__label" style="font-weight:400;color:var(--muted,#9aa8a1)">No categories yet.</span>
            @endforelse
        </div>
    </div>

    {{-- Brand --}}
    <div @class(['jtc-filters__group' => ! $bare]) @if($bare) style="margin-top:18px" @endif>
        <div class="jtc-filters__label">Brand</div>
        <div class="jtc-filters__list">
            @forelse($allBrands as $b)
                <label class="jtc-filters__check">
                    <input type="checkbox" @checked(in_array($b['name'], $brands)) wire:click="toggleBrand('{{ $b['name'] }}')">
                    {{ $b['name'] }} <span>({{ $b['count'] }})</span>
                </label>
            @empty
                <span class="jtc-filters__label" style="font-weight:400;color:var(--muted,#9aa8a1)">No brands yet.</span>
            @endforelse
        </div>
    </div>

    {{-- Size --}}
    @if(count($allSizes) > 0)
        <div @class(['jtc-filters__group' => ! $bare]) @if($bare) style="margin-top:18px" @endif>
            <div class="jtc-filters__label">Size</div>
            <div class="jtc-filters__list" style="flex-direction:row;flex-wrap:wrap;gap:8px">
                @foreach($allSizes as $z)
                    <label class="jtc-filters__check">
                        <input type="checkbox" @checked(in_array($z, $sizes)) wire:click="toggleSize('{{ $z }}')">
                        {{ $z }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Offers --}}
    <div @class(['jtc-filters__group' => ! $bare]) @if($bare) style="margin-top:18px" @endif>
        <div class="jtc-filters__label">Offers</div>
        <div class="jtc-filters__list">
            @foreach($allOffers as $key => $label)
                <label class="jtc-filters__check">
                    <input type="checkbox" @checked(in_array($key, $offers)) wire:click="toggleOffer('{{ $key }}')">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
</div>
