@php
    $trustIcons = [
        'truck' => '<path d="M3 7h13v8H3z"></path><path d="M16 10h3l2 2v3h-5z"></path><circle cx="7" cy="18" r="1.6"></circle><circle cx="17" cy="18" r="1.6"></circle>',
        'shield' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line>',
        'return' => '<path d="M3 9 12 4l9 5v8l-9 5-9-5z"></path><path d="M3 9l9 5 9-5"></path>',
        'support' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
    ];
@endphp
<section class="jtc-trust">
    <div class="jtc-trust__inner">
        @foreach($items as $item)
            <div class="jtc-trust__item" wire:key="trust-{{ $loop->index }}">
                <span class="jtc-trust__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">{!! $trustIcons[$item['icon']] ?? '' !!}</svg>
                </span>
                <div><h4>{{ $item['title'] }}</h4><p>{{ $item['description'] }}</p></div>
            </div>
        @endforeach
    </div>
</section>
