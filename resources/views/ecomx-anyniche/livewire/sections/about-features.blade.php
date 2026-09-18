@if(count($items))
    <div class="jtc-about__grid">
        @foreach($items as $item)
            <div class="jtc-about__card" wire:key="about-feature-{{ $loop->index }}">
                <h3>{{ $item['title'] }}</h3>
                <p>{{ $item['description'] }}</p>
            </div>
        @endforeach
    </div>
@else
    <div></div>
@endif
