<div x-data="{ searchModalCatOpen: false }"
    class="jtc-modal-scrim" :class="$store.ui.searchOpen && 'is-open'" @click="$store.ui.searchOpen = false" x-cloak
    @keydown.escape.window="$store.ui.searchOpen = false">
    <div class="jtc-modal jtc-modal--search" @click.stop
         x-effect="if ($store.ui.searchOpen) $refs.searchInput?.focus()">

        <div class="jtc-searchmodal__top">
            <div class="jtc-modal__head">
                <button class="jtc-modal__close" aria-label="Close search" @click="$store.ui.searchOpen = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
                </button>
                <h3>Search products</h3>
            </div>

            <form class="jtc-search jtc-search--modal" role="search" @submit.prevent>
                <button type="button" class="jtc-search__cat" @click="searchModalCatOpen = !searchModalCatOpen">
                    <span>{{ $category ?? 'All categories' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>

                <div class="jtc-search__dropdown" :class="searchModalCatOpen && 'is-open'" x-cloak>
                    <button type="button" wire:click="selectCategory(null)" @click="searchModalCatOpen = false"
                            @class(['jtc-search__option' => true, 'is-active' => ! $category])>All categories</button>
                    @foreach ($categories as $cat)
                        <button type="button" wire:click="selectCategory(@js($cat->name))" @click="searchModalCatOpen = false"
                                @class(['jtc-search__option' => true, 'is-active' => $category === $cat->name])>{{ $cat->name }}</button>
                    @endforeach
                </div>
                <div class="jtc-search__scrim" x-show="searchModalCatOpen" @click="searchModalCatOpen = false" x-cloak></div>

                <input type="search" class="jtc-search__input" placeholder="Search products, categories…"
                       aria-label="Search products" x-ref="searchInput"
                       wire:model.live.debounce.300ms="q">
                <span class="jtc-search__go">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
            </form>
        </div>

        <div class="jtc-search-results">
            <div wire:loading.delay class="jtc-search-results__loading">Searching…</div>

            <div wire:loading.remove.delay>
                @if (trim($q) === '')
                    <p class="jtc-search-results__hint">Start typing to search our catalogue.</p>
                @elseif (count($this->results) === 0)
                    <p class="jtc-search-results__hint">No products found for "{{ $q }}".</p>
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
                                @if ($p['inStock'])
                                    <a href="{{ $p['url'] }}" class="jtc-btn jtc-btn--primary jtc-searchresult__add"
                                       wire:navigate>View</a>
                                @else
                                    <span class="jtc-btn jtc-btn--primary jtc-searchresult__add" style="opacity:.5;pointer-events:none">Out of Stock</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('ecomx-anyniche.shop', ['q' => $q]) }}" class="jtc-search-results__seeall">
                        See all results for "{{ $q }}"
                        <x-anyniche::icons.arrow />
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
