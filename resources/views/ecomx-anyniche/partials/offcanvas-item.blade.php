@php
    $hasChildren = ! empty($item['children']);
    $target = $item['new_tab'] ? ' target="_blank" rel="noopener"' : '';
    // Resolve once and check the actual URL, not just the id: file_path()
    // returns null if the underlying File was deleted after this item was
    // saved (menus.json still holds the stale image_id), and rendering an
    // <img src=""> in that case would show a broken image instead of
    // falling back to the configured icon.
    $imageUrl = ! empty($item['image_id']) ? file_path($item['image_id']) : null;
@endphp
@if ($hasChildren)
    <div x-data="{ open: false }" class="jtc-offcanvas__group">
        <div class="jtc-offcanvas__row">
            <a href="{{ $item['url'] }}"{!! $target !!} class="jtc-offcanvas__link" @click="drawer = false">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="jtc-offcanvas__link-img">
                @elseif (!empty($item['icon']))
                    <span class="jtc-offcanvas__icon"><x-anyniche::icon :name="$item['icon']" /></span>
                @endif
                <span>{{ $item['label'] }}</span>
            </a>
            <button type="button" class="jtc-offcanvas__toggle" :class="open && 'is-open'"
                @click="open = !open" :aria-expanded="open" aria-label="Toggle {{ $item['label'] }} submenu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </div>
        <div class="jtc-offcanvas__submenu" x-show="open" x-collapse x-cloak>
            @foreach ($item['children'] as $child)
                @php $childImageUrl = ! empty($child['image_id']) ? file_path($child['image_id']) : null; @endphp
                <a href="{{ $child['url'] }}"{!! $child['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!}
                    class="jtc-offcanvas__link jtc-offcanvas__link--sub" @click="drawer = false">
                    @if ($childImageUrl)
                        <img src="{{ $childImageUrl }}" alt="" class="jtc-offcanvas__link-img">
                    @elseif (!empty($child['icon']))
                        <span class="jtc-offcanvas__icon"><x-anyniche::icon :name="$child['icon']" /></span>
                    @endif
                    <span>{{ $child['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    <a href="{{ $item['url'] }}"{!! $target !!} class="jtc-offcanvas__link" @click="drawer = false">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="" class="jtc-offcanvas__link-img">
        @elseif (!empty($item['icon']))
            <span class="jtc-offcanvas__icon"><x-anyniche::icon :name="$item['icon']" /></span>
        @endif
        <span>{{ $item['label'] }}</span>
    </a>
@endif
