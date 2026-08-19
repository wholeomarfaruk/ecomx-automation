<div>
    {{-- Active sections + order for this page are managed from Admin > Page Sections --}}
    {{-- (App\Support\EcomxFashion\PageSectionRegistry, file-backed — no DB), resolved --}}
    {{-- to Livewire tags via config('ecomx-fashion.sections'). --}}
    {{-- @foreach(\App\Support\EcomxFashion\PageSectionRegistry::activeKeysForPage('home') as $section)
        @livewire(config(\App\Support\EcomxFashion\ActiveTheme::slug() . ".sections.$section"), key('home-' . $section))
    @endforeach --}}
</div>
