@php
    $shopMenuItems = \App\Support\EcomxAnyniche\MenuRegistry::items('shop');
    $categoryMenuItems = \App\Support\EcomxAnyniche\MenuRegistry::items('categories');
@endphp
<div class="jtc-offcanvas" :class="drawer && 'is-open'" x-cloak>
    <div class="jtc-offcanvas__head">
        <span>Menu</span>
        <button class="jtc-offcanvas__close" aria-label="Close menu" @click="drawer = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
        </button>
    </div>

    @if ($shopMenuItems)
        <div class="jtc-offcanvas__label">Shop</div>
        <nav class="jtc-offcanvas__nav">
            @foreach ($shopMenuItems as $item)
                @include('ecomx-anyniche.partials.offcanvas-item', ['item' => $item])
            @endforeach
        </nav>
    @endif

    @if ($categoryMenuItems)
        <div class="jtc-offcanvas__label">Categories</div>
        <nav class="jtc-offcanvas__nav" style="padding-bottom:10px">
            @foreach ($categoryMenuItems as $item)
                @include('ecomx-anyniche.partials.offcanvas-item', ['item' => $item])
            @endforeach
        </nav>
    @endif
</div>
