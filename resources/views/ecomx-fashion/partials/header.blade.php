{{-- Main nav — plain links only, no dropdown/mega-menu submenus. --}}
@php
    $nav = [
        ['label'=>'New In','route'=>'ecomx-fashion.shop','key'=>'new','flash'=>false],
        ['label'=>'Women','route'=>'ecomx-fashion.shop','key'=>'women','flash'=>false,'params'=>['cat'=>['women']]],
        ['label'=>'Men','route'=>'ecomx-fashion.shop','key'=>'men','flash'=>false,'params'=>['cat'=>['men']]],
        ['label'=>'Accessories','route'=>'ecomx-fashion.shop','key'=>'acc','flash'=>false],
        ['label'=>'Flash Sale','route'=>'ecomx-fashion.shop','key'=>'sale','flash'=>true,'params'=>['offer'=>['flash_sale']]],
    ];

    $active = $active ?? '';

    // Symbol/mark logo (Admin > Site Settings > General > Branding). Falls
    // back to the theme's bundled mark whenever site_logo_symbol isn't set,
    // or points at a File row that's missing/deleted/has no image — never
    // let a bad setting break the header.
    $brandMarkUrl = null;
    try {
        if ($logoSymbolId = \App\Models\Setting::get('site_logo_symbol')) {
            $brandMarkUrl = file_path($logoSymbolId);
        }
    } catch (\Throwable $e) {
        $brandMarkUrl = null;
    }
    $brandMarkUrl ??= asset('frontend/img/seldom-rounded.png');
    $siteName = \App\Models\Setting::get('site_name', 'Seldom Fashion') ?: 'Seldom Fashion';

    $isNavActive = function ($n) use ($active) {
        // Explicit active key থাকলে সেটা priority পাবে
        if ($active === $n['key']) {
            return true;
        }

        // Current route shop না হলে inactive
        if (!request()->routeIs($n['route'])) {
            return false;
        }

        // URL query parameter pattern match
        $cat = request()->query('cat');
        $offer = request()->query('offer');

        return match ($n['key']) {
            'women' => is_array($cat) && in_array('women', $cat),
            'men'   => is_array($cat) && in_array('men', $cat),
            'sale'  => is_array($offer) && in_array('flash_sale', $offer),
            'acc'   => is_array($cat) && in_array('accessories', $cat),
            'new'   => !$cat && !$offer,
            default => false,
        };
    };
@endphp

<div x-data="{ mega:null, drawer:false }" @keydown.escape.window="drawer=false;mega=null">
    <div class="topbar">
        <div class="topbar__inner">
            <span>Free delivery across Bangladesh on orders over ৳5,000 · bKash, Nagad &amp; COD accepted</span>
            <div class="topbar__links">
                <a href="{{ route('ecomx-fashion.track') }}">Track Order</a>
                <a href="{{ route('ecomx-fashion.home') }}">About Us</a>
                <a href="tel:{{ config('ecomx-fashion.phone') }}">Contact</a>
            </div>
        </div>
    </div>

    <header class="header" @mouseleave="mega=null">
        <div class="header__inner">
            <button class="icon-btn header__hamburger" style="background:none;border:none" @click="drawer=true" aria-label="Menu">☰</button>

            <a href="{{ route('ecomx-fashion.home') }}" class="brand" @mouseenter="mega=null">
                <img src="{{ $brandMarkUrl }}" alt="{{ $siteName }}" class="brand__mark">
                <span class="brand__text">
                    <span class="brand__name">SELDOM</span>
                    <span class="brand__sub" aria-hidden="true"><span>F</span><span>A</span><span>S</span><span>H</span><span>I</span><span>O</span><span>N</span></span>
                </span>
            </a>

            <nav class="nav" aria-label="Main">
                @foreach($nav as $n)
                    <div class="nav__item">
                        <a href="{{ route($n['route'], $n['params'] ?? []) }}" class="nav__link  {{ $isNavActive($n) ? 'is-active' : '' }}" @mouseenter="mega=null">
                            {{ $n['label'] }}
                            @if($n['flash'])<span class="nav__badge">⚡HOT</span>@endif
                        </a>
                    </div>
                @endforeach
            </nav>

            <div class="header__actions" @mouseenter="mega=null">
                <div class="search">
                    <input type="search" placeholder="Search" aria-label="Search" @click.stop="$store.ui.searchOpen=true" readonly>
                    <button class="icon-btn" aria-label="Search" @click.stop="$store.ui.searchOpen=true"><x-icon name="search" /></button>
                </div>
                <button class="icon-btn" @click="$store.ui.supportOpen=true" aria-label="Support"><x-icon name="phone" /></button>
                @php $initialWishlistCount = \App\Models\WishlistItem::forDevice(request()->attributes->get('device'))->count(); @endphp
                <button class="icon-btn" style="position:relative" x-data x-init="$store.ui.wishlistCount = {{ $initialWishlistCount }}" @click="$store.ui.wishlistOpen=true" aria-label="Wishlist">
                    <x-icon name="heart" />
                    <span class="cart-badge" x-text="$store.ui.wishlist" x-show="$store.ui.wishlist > 0"></span>
                </button>
                @php
                    $cartDevice = request()->attributes->get('device');
                    $initialCartCount = $cartDevice
                        ? (int) (\App\Models\Cart::where('customer_id', null)->where('device_id', $cartDevice->id)->first()?->items()->sum('quantity') ?? 0)
                        : 0;
                @endphp
                <button class="icon-btn" style="position:relative" x-data x-init="$store.ui.cartCount = {{ $initialCartCount }}" @click="$store.ui.cartOpen=true" aria-label="Cart">
                    <x-icon name="cart" />
                    <span class="cart-badge" x-text="$store.ui.cart" x-show="$store.ui.cart > 0"></span>
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
                        $authInitial = strtoupper(mb_substr(trim($authUser->name) ?: '?', 0, 1));
                    @endphp
                    <div class="dropdown-wrap" x-data="{ open: false }" style="position:relative">
                        <button class="account-trigger" @click="open=!open" aria-label="Account" aria-haspopup="true" :aria-expanded="open">
                            @if ($authAvatarUrl)
                                <img src="{{ $authAvatarUrl }}" alt="{{ $authUser->name }}" class="account-trigger__avatar">
                            @else
                                <span class="account-trigger__initial">{{ $authInitial }}</span>
                            @endif
                            <span class="account-trigger__chevron"><x-icon name="chevron-down" /></span>
                        </button>
                        <div class="dropdown" x-show="open" x-cloak @click.outside="open=false" x-transition style="right:0;left:auto">
                            <div style="padding:10px 12px;border-bottom:1px solid rgba(var(--pri-rgb),.08);margin-bottom:4px">
                                <p style="font-size:13px;font-weight:600;color:var(--pri);margin:0">{{ auth()->user()->name }}</p>
                                @if(auth()->user()->phone)
                                    <p style="font-size:11.5px;color:rgba(var(--pri-rgb),.5);margin:2px 0 0">{{ auth()->user()->phone }}</p>
                                @endif
                            </div>
                            <a href="{{ route('ecomx-fashion.track') }}" class="dropdown__link" wire:navigate>My Orders</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown__link" style="width:100%;text-align:left;border:none;background:none;cursor:pointer;font-family:inherit">Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <button class="icon-btn" @click="$store.ui.authOpen=true" aria-label="Account"><x-icon name="user" /></button>
                @endauth
            </div>
        </div>

    </header>

    {{-- Mobile drawer --}}
    <template x-if="drawer">
        <div class="overlay" @click.self="drawer=false">
            <div class="drawer">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <span style="display:flex;align-items:center;gap:10px">
                        <img src="{{ $brandMarkUrl }}" alt="{{ $siteName }}" class="brand__mark">
                        <span class="brand__name">{{ $siteName }}</span>
                    </span>
                    <button class="modal__close" @click="drawer=false" aria-label="Close">✕</button>
                </div>
                @foreach($nav as $n)
                    <a href="{{ route($n['route'], $n['params'] ?? []) }}" class="drawer__link"><span>{{ $n['label'] }}</span><span style="color:rgba(var(--pri-rgb),.35)">→</span></a>
                @endforeach
                <a href="{{ route('ecomx-fashion.reviews') }}" style="margin-top:18px;font-size:13.5px;color:var(--ac2)">Customer Reviews ★ 4.8</a>
                <div style="margin-top:20px;display:flex;flex-direction:column;gap:12px;border-top:1px solid rgba(var(--pri-rgb),.07);padding-top:18px">
                    <a href="{{ route('ecomx-fashion.track') }}" style="font-size:13.5px">Track Order</a>
                    <a href="{{ route('ecomx-fashion.home') }}" style="font-size:13.5px">About Us</a>
                    <a href="tel:{{ config('ecomx-fashion.phone') }}" style="font-size:13.5px">Contact</a>
                </div>
            </div>
        </div>
    </template>
</div>
