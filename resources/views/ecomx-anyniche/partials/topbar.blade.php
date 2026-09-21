@php
    $topbarMenuItems = \App\Support\EcomxAnyniche\MenuRegistry::items('header');
@endphp
<div class="jtc-topbar">
    <div class="jtc-topbar__inner">
        <div class="jtc-topbar__promo">
            <span class="jtc-topbar__dot"></span>
            <span>Free doorstep delivery on orders over ৳2,000 · nationwide</span>
        </div>
        @if ($topbarMenuItems)
            <nav class="jtc-topbar__nav">
                @foreach ($topbarMenuItems as $item)
                    <a href="{{ $item['url'] }}"{!! $item['new_tab'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $item['label'] }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</div>
