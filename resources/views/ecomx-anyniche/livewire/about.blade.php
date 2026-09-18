<div class="jtc-about">
    <div class="jtc-about__hero">
        <nav class="jtc-shop__crumb" style="margin-bottom:14px">
            <a href="{{ route('ecomx-anyniche.home') }}" style="color:inherit;text-decoration:none">Home</a>
            <span>/</span>
            <span style="color:#14201c;font-weight:600">About us</span>
        </nav>
        @if(in_array('about-hero', $sections, true))
            @livewire('ecomx-anyniche.sections.about-hero', [], key('about-hero'))
        @endif
    </div>

    {{-- Remaining content managed from Admin > Frontend Engine > Pages > About Us --}}
    {{-- (App\Support\EcomxAnyniche\PageSectionRegistry), same pattern as Home's sections. --}}
    @foreach($sections as $section)
        @continue($section === 'about-hero')
        @livewire(
            config(\App\Support\EcomxAnyniche\ActiveTheme::slug() . ".sections.$section"),
            [],
            key('about-' . $section)
        )
    @endforeach
</div>
