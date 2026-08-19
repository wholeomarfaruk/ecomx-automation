<div x-data
    x-show="$store.ui.searchOpen" x-cloak class="modal search-modal" style="align-items:flex-start"
    @click.self="$store.ui.searchOpen=false" @keydown.escape.window="$store.ui.searchOpen=false">
    <div class="modal__box search-modal__box" @click.outside="$store.ui.searchOpen=false">
        <div class="search-modal__bar">
            <x-icon name="search" />
            <input type="search" wire:model.live.debounce.300ms="q" placeholder="Search products, categories…" aria-label="Search" x-ref="searchInput" x-effect="$store.ui.searchOpen && $nextTick(() => $refs.searchInput.focus())">
            <button class="modal__close" @click="$store.ui.searchOpen=false" aria-label="Close">✕</button>
        </div>

        @if (trim($q) !== '' && count($this->results) === 0)
            <p class="muted search-modal__empty">No results for "{{ $q }}".</p>
        @endif

        @if (count($this->results) > 0)
            <div class="search-modal__results">
                @foreach ($this->results as $p)
                    <a href="{{ $p['url'] }}" class="search-modal__item {{ ! $p['inStock'] ? 'is-oos' : '' }}" @if(! $p['inStock']) @click.prevent @endif>
                        <div class="search-modal__item-media">
                            <x-ux-img :id="$p['img']" :w="160" :alt="$p['name']" />
                            @if (! $p['inStock'])
                                <span class="search-modal__oos-badge">Out of Stock</span>
                            @endif
                        </div>
                        <div class="search-modal__item-body">
                            <span class="search-modal__item-name">{{ $p['name'] }}</span>
                            <span class="muted" style="font-size:11.5px">{{ $p['cat'] }}</span>
                        </div>
                        <span class="search-modal__item-price">
                            @if ($p['sale'])
                                <span class="search-modal__price-old">৳{{ number_format($p['price']) }}</span>
                            @endif
                            <span class="{{ $p['sale'] ? 'search-modal__price-sale' : '' }}">৳{{ number_format($p['sale'] ?? $p['price']) }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if (trim($q) === '')
            <div class="search-modal__hint">
                <p class="muted" style="font-size:12.5px;margin-bottom:10px">Popular searches</p>
                <div class="search-modal__tags">
                    <button type="button" class="chip" wire:click="$set('q', 'Dress')">Dress</button>
                    <button type="button" class="chip" wire:click="$set('q', 'Shirt')">Shirt</button>
                    <button type="button" class="chip" wire:click="$set('q', 'Trouser')">Trouser</button>
                    <button type="button" class="chip" wire:click="$set('q', 'Coat')">Coat</button>
                </div>
            </div>
        @endif
    </div>
</div>
