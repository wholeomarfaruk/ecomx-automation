<div>
@if(count($chips) > 0)
    <section class="jtc-section jtc-section--tight">
        <div class="jtc-shell">
            <div style="margin-bottom:26px">
                <h2 class="jtc-h2">Discover more</h2>
            </div>
            <div class="jtc-chips">
                @foreach($chips as $chip)
                    <a href="{{ $chip['url'] }}" class="jtc-chip">{{ $chip['name'] }}</a>
                @endforeach
            </div>
        </div>
    </section>
@endif
</div>
