@php $items = [
    ['label'=>'Home','icon'=>'home','route'=>'ecomx-fashion.home'],
    ['label'=>'Shop','icon'=>'grid','route'=>'ecomx-fashion.shop'],
    ['label'=>'Search','icon'=>'search','action'=>'searchOpen'],
    ['label'=>'Cart','icon'=>'cart','action'=>'cartOpen'],
    ['label'=>'Account','icon'=>'user','action'=>'authOpen'],
]; $ab = $activeBottom ?? 'home'; @endphp
<nav class="bottom-nav" aria-label="Bottom navigation" x-data>
    @foreach($items as $n)
        @if(isset($n['action']))
            <button type="button" class="bottom-nav__item {{ $ab === strtolower($n['label']) ? 'is-active' : '' }}" style="position:relative" @click="$store.ui.{{ $n['action'] }}=true">
                <span class="bottom-nav__icon"><x-icon :name="$n['icon']" /></span>
                <span>{{ $n['label'] }}</span>
                @if($n['action'] === 'cartOpen')<span class="cart-badge bottom-nav__badge" x-text="$store.ui.cart" x-show="$store.ui.cart > 0"></span>@endif
            </button>
        @else
            <a href="{{ route($n['route']) }}" class="bottom-nav__item {{ $ab === strtolower($n['label']) ? 'is-active' : '' }}">
                <span class="bottom-nav__icon"><x-icon :name="$n['icon']" /></span>
                <span>{{ $n['label'] }}</span>
            </a>
        @endif
    @endforeach
</nav>
