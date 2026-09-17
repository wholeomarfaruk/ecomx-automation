@php
    $offcanvasCategories = \App\Models\Category::active()
        ->whereNull('parent_id')
        ->with('children')
        ->get();
@endphp
<div class="jtc-offcanvas" :class="drawer && 'is-open'" x-cloak>
    <div class="jtc-offcanvas__head">
        <span>Menu</span>
        <button class="jtc-offcanvas__close" aria-label="Close menu" @click="drawer = false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
        </button>
    </div>

    <div class="jtc-offcanvas__label">Shop</div>
    <nav class="jtc-offcanvas__nav">
        <a href="{{ route('ecomx-anyniche.shop') }}" class="jtc-offcanvas__link">Shop all</a>
    </nav>

    <div class="jtc-offcanvas__label">Categories</div>
    <nav class="jtc-offcanvas__nav" style="padding-bottom:10px">
        @foreach ($offcanvasCategories as $cat)
            @if ($cat->children->isNotEmpty())
                <div x-data="{ open: false }" class="jtc-offcanvas__group">
                    <div class="jtc-offcanvas__row">
                        <a href="{{ route('ecomx-anyniche.category', $cat->slug) }}" class="jtc-offcanvas__link" @click="drawer = false">
                            <span>{{ $cat->name }}</span>
                        </a>
                        <button type="button" class="jtc-offcanvas__toggle" :class="open && 'is-open'"
                            @click="open = !open" :aria-expanded="open" aria-label="Toggle {{ $cat->name }} subcategories">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                    {{-- Subcategories aren't shown as links yet — this theme has no
                         dedicated subcategory route/page, only the flat "category"
                         route, so a child link here would 404. --}}
                </div>
            @else
                <a href="{{ route('ecomx-anyniche.category', $cat->slug) }}" class="jtc-offcanvas__link" @click="drawer = false">
                    <span>{{ $cat->name }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</div>
