@php
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

    $headerLogoUrl = null;
    try {
        if ($logoId = \App\Models\Setting::get('site_logo_symbol')) {
            $headerLogoUrl = file_path($logoId);
        }
    } catch (\Throwable $e) {
        $headerLogoUrl = null;
    }
    $headerLogoUrl ??= asset('logo/ecomx-square-logo.png');

    $headerDevice = request()->attributes->get('device');
    $initialWishlistCount = \App\Models\WishlistItem::forDevice($headerDevice)->count();
    $initialCartCount = $headerDevice
        ? (int) (\App\Models\Cart::where('customer_id', null)->where('device_id', $headerDevice->id)->first()?->items()->sum('quantity') ?? 0)
        : 0;
@endphp
<header class="jtc-header" x-data
    x-init="$store.ui.wishlistCount = {{ $initialWishlistCount }}; $store.ui.cartCount = {{ $initialCartCount }}">
    <div class="jtc-header__inner">

        <button class="jtc-icon-btn jtc-hamburger" aria-label="Open menu" @click="drawer = true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <a href="{{ route('ecomx-anyniche.home') }}" class="jtc-logo">
            <span class="jtc-logo__mark">
                <img src="{{ $headerLogoUrl }}" alt="{{ $siteName }}">
            </span>
            <span class="jtc-logo__text">
                <span class="jtc-logo__name">{{ $siteName }}</span>
            </span>
        </a>

        @livewire('ecomx-anyniche.header-search')

        <div class="jtc-actions">
            <button class="jtc-round-btn jtc-round-btn--search jtc-round-btn--search-trigger" aria-label="Open search" title="Search" @click="$store.ui.searchOpen = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>

            <button class="jtc-round-btn jtc-round-btn--support" aria-label="Call support" title="Call support" @click="$store.ui.supportOpen = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.94.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.87.35 1.81.59 2.81.72A2 2 0 0 1 22 16.92z"></path></svg>
            </button>

            <button class="jtc-round-btn jtc-round-btn--wishlist" aria-label="Open wishlist" title="Wishlist" @click="$store.ui.wishlistOpen = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="21" height="21"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"></path></svg>
                <span class="jtc-cart-badge" x-text="$store.ui.wishlist" x-show="$store.ui.wishlist > 0" x-cloak></span>
            </button>

            <button class="jtc-round-btn jtc-round-btn--cart" aria-label="Open cart" @click="$store.ui.cartOpen = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" width="23" height="23"><circle cx="9" cy="21" r="1.7"></circle><circle cx="19" cy="21" r="1.7"></circle><path d="M2.5 3h2.2l2.1 12.1a1.8 1.8 0 0 0 1.8 1.5h9.1a1.8 1.8 0 0 0 1.8-1.4l1.6-7.2H6"></path></svg>
                <span class="jtc-cart-badge" x-text="$store.ui.cart" x-show="$store.ui.cart > 0" x-cloak></span>
            </button>

            @auth
                @php
                    $authUser = auth()->user();
                    $authAvatarUrl = null;
                    try {
                        $authAvatarUrl = $authUser->avatar_id ? file_path($authUser->avatar_id) : null;
                    } catch (\Throwable $e) {
                        $authAvatarUrl = null;
                    }
                @endphp
                <div class="jtc-accountmenu" x-data="{ accountMenuOpen: false }" @click.outside="accountMenuOpen = false">
                    <button type="button" class="jtc-round-btn jtc-round-btn--account"
                            aria-label="Account menu for {{ $authUser->name }}"
                            title="{{ $authUser->name }}"
                            @click="accountMenuOpen = !accountMenuOpen">
                        @if ($authAvatarUrl)
                            <img class="jtc-round-btn__avatar" src="{{ $authAvatarUrl }}" alt="{{ $authUser->name }}">
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                        @endif
                        <svg class="jtc-accountmenu__caret" :class="accountMenuOpen && 'is-open'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>

                    <div class="jtc-accountmenu__dropdown" x-show="accountMenuOpen" x-cloak :class="accountMenuOpen && 'is-open'">
                        <div class="jtc-accountmenu__head">{{ $authUser->name }}</div>
                        <a href="{{ route('ecomx-anyniche.account') }}" class="jtc-accountmenu__link" @click="accountMenuOpen = false" wire:navigate>My Account</a>
                        <a href="{{ route('ecomx-anyniche.track') }}" class="jtc-accountmenu__link" @click="accountMenuOpen = false" wire:navigate>My Orders</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="jtc-accountmenu__link jtc-accountmenu__link--logout" @click="accountMenuOpen = false" style="width:100%;text-align:left;border:none;background:none;cursor:pointer;font-family:inherit">Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <button type="button" class="jtc-round-btn jtc-round-btn--account" aria-label="Login / Sign up" title="Login / Sign up" @click="$store.ui.authOpen = true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                </button>
            @endauth
        </div>
    </div>
</header>
