<div>
    {{-- Active sections + order for this page are managed from Admin > Page Sections --}}
    {{-- (App\Support\EcomxFashion\PageSectionRegistry, file-backed — no DB), resolved --}}
    {{-- to Livewire tags via config('ecomx-fashion.sections'). $sections is --}}
    {{-- pre-filtered by Home::activeSections() to drop any section whose --}}
    {{-- Livewire tag can't be resolved, so one broken section can't blank --}}
    {{-- the whole page. --}}
    {{-- Lazy sections load on-mount ('lazy' => 'on-load') rather than on scroll --}}
    {{-- intersection, so they all start fetching right after the initial page --}}
    {{-- response instead of only when the visitor scrolls each one into view --}}
    {{-- (see Home::sectionIsLazy()) — background-loaded and ready by the time --}}
    {{-- the visitor scrolls down, instead of blank-then-skeleton-then-content. --}}
    @foreach($sections as $section)
        @livewire(
            config(\App\Support\EcomxFashion\ActiveTheme::slug() . ".sections.$section"),
            $this->sectionIsLazy($section) ? ['lazy' => 'on-load'] : [],
            key('home-' . $section)
        )
    @endforeach
</div>
