<div class="jtc-search" role="search" x-data="{ focused: false, searchCatOpen: false, selectedCategory: @js($category) }" @click.outside="focused = false">
    <button type="button" class="jtc-search__cat" @click="searchCatOpen = !searchCatOpen">
        <span x-text="selectedCategory || 'All categories'">All categories</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="6 9 12 15 18 9"></polyline></svg>
    </button>

    <div class="jtc-search__dropdown" :class="searchCatOpen && 'is-open'" x-cloak>
        <button type="button" class="jtc-search__option" :class="!selectedCategory && 'is-active'"
                wire:click="selectCategory(null)" @click="selectedCategory = null; searchCatOpen = false">All categories</button>
        @foreach ($categories as $cat)
            <button type="button" class="jtc-search__option"
                    :class="selectedCategory === @js($cat['name']) && 'is-active'"
                    wire:click="selectCategory(@js($cat['name']))" @click="selectedCategory = @js($cat['name']); searchCatOpen = false">{{ $cat['name'] }}</button>
        @endforeach
    </div>
    <div class="jtc-search__scrim" x-show="searchCatOpen" @click="searchCatOpen = false" x-cloak></div>

    <input type="search" class="jtc-search__input" placeholder="Search products, categories…"
           aria-label="Search products"
           wire:model.live.debounce.400ms="query"
           @focus="focused = true"
           @keydown.escape="focused = false; $event.target.blur()"
           @keydown.enter.prevent="if ($wire.query.trim()) window.location = '{{ route('ecomx-anyniche.shop') }}?q=' + encodeURIComponent($wire.query)">
    <a href="{{ route('ecomx-anyniche.shop') }}?q={{ urlencode($query) }}" class="jtc-search__go" aria-label="Search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    </a>

    <div class="jtc-headersearch__panel" x-show="focused && $wire.query.trim().length > 0" x-cloak>
        <div wire:loading.delay class="jtc-search-results__loading">Searching…</div>

        <div wire:loading.remove.delay>
            @if (trim($query) === '')
                {{-- input is focused but empty; panel stays hidden via x-show above --}}
            @elseif (count($this->results) === 0)
                <p class="jtc-search-results__hint">No products found for "{{ $query }}".</p>
            @else
                <ul class="jtc-search-results__list">
                    @foreach ($this->results as $p)
                        <li class="jtc-searchresult">
                            <a href="{{ $p['url'] }}" class="jtc-searchresult__media">
                                <img src="{{ $p['img'] }}" alt="{{ $p['name'] }}" loading="lazy">
                            </a>
                            <div class="jtc-searchresult__body">
                                <a href="{{ $p['url'] }}" class="jtc-searchresult__name">{{ $p['name'] }}</a>
                                <div class="jtc-searchresult__prices">
                                    @if ($p['sale'])
                                        <span class="jtc-searchresult__price jtc-searchresult__price--sale">৳{{ number_format($p['sale']) }}</span>
                                        <span class="jtc-searchresult__was">৳{{ number_format($p['price']) }}</span>
                                    @else
                                        <span class="jtc-searchresult__price">৳{{ number_format($p['price']) }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $p['url'] }}" class="jtc-btn jtc-btn--primary jtc-searchresult__add" wire:navigate>
                                View
                            </a>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('ecomx-anyniche.shop') }}?q={{ urlencode($query) }}" class="jtc-search-results__seeall">
                    See all results for "{{ $query }}"
                    <x-anyniche::icons.arrow />
                </a>
            @endif
        </div>
    </div>
</div>
