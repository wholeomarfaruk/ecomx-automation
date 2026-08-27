<div>
    {{-- Active sections + order for this page are managed from Admin > Page Sections --}}
    {{-- (App\Support\EcomxFashion\PageSectionRegistry, file-backed — no DB), resolved --}}
    {{-- to Livewire tags via config('ecomx-fashion.sections'). $sections is --}}
    {{-- pre-filtered by Home::activeSections() to drop any section whose --}}
    {{-- Livewire tag can't be resolved, so one broken section can't blank --}}
    {{-- the whole page. --}}
    @foreach($sections as $section)
        @livewire(config(\App\Support\EcomxFashion\ActiveTheme::slug() . ".sections.$section"), key('home-' . $section))
    @endforeach
</div>
