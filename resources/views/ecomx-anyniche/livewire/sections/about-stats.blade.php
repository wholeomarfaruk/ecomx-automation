@if(count($items))
    <div class="jtc-about__stats">
        @foreach($items as $stat)
            <div class="jtc-about__stat" wire:key="about-stat-{{ $loop->index }}">
                <div class="jtc-about__stat-val">{{ $stat['val'] }}</div>
                <div class="jtc-about__stat-label">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>
@else
    <div></div>
@endif
