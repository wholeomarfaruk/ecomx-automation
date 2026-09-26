<div class="jtc-about__cta">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $subtitle }}</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('ecomx-anyniche.track') }}" class="jtc-btn jtc-btn--outline">{{ $trackLabel }}</a>
        @if($tel = \App\Support\ContactInfo::telHref())
            <a href="{{ $tel }}" class="jtc-btn jtc-btn--primary">{{ $callLabel }}</a>
        @endif
    </div>
</div>
