<div>
    {{-- Active sections + order for this page are managed from Admin > Page Sections --}}
    {{-- (App\Support\EcomxAnyniche\PageSectionRegistry, file-backed — no DB), resolved --}}
    {{-- to Livewire tags via config('ecomx-anyniche.sections'). $sections is --}}
    {{-- pre-filtered by Home::activeSections() to drop any section whose --}}
    {{-- Livewire tag can't be resolved, so one broken section can't blank --}}
    {{-- the whole page. --}}
    {{-- Lazy sections load on-mount ('lazy' => 'on-load') rather than on scroll --}}
    {{-- intersection, so they all start fetching right after the initial page --}}
    {{-- response instead of only when the visitor scrolls each one into view --}}
    {{-- (see Home::sectionIsLazy()) — background-loaded and ready by the time --}}
    {{-- the visitor scrolls down, instead of blank-then-skeleton-then-content. --}}
    @php
        $categoryRowTag = 'ecomx-anyniche.sections.category-row';
    @endphp
    @foreach($sections as $section)
        @php
            $tag = config(\App\Support\EcomxAnyniche\ActiveTheme::slug() . ".sections.$section");
        @endphp
        @livewire(
            $tag,
            array_merge(
                $this->sectionIsLazy($section) ? ['lazy' => 'on-load'] : [],
                $tag === $categoryRowTag ? ['sectionKey' => $section] : []
            ),
            key('home-' . $section)
        )
    @endforeach
</div>
