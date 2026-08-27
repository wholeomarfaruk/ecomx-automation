{{--
    Admin-preview-only wrapper — a plain controller
    (App\Http\Controllers\Admin\LandingPagePreviewController) has no
    Livewire #[Layout(...)] attribute to rely on (that's a Livewire
    component-class concern, invisible outside a real Livewire render
    cycle), so it composes the layout itself as a genuine Blade component
    invocation instead. The public route
    (App\Livewire\LandingPageEngine\LandingPageRenderer) does NOT use this
    file — it gets the layout via #[Layout] + ->layoutData() instead (see
    that class's docblock) and renders
    landingpage-renderer.blade.php's content directly.
--}}
<x-landingpage
    :title="$title ?? null"
    :meta-description="$metaDescription ?? null"
    :meta-image="$metaImage ?? null"
>
    <div>
        @livewire($componentTag, ['landingPageId' => $landingPageId], key('landingpage-root-preview-' . $landingPageId))
    </div>
</x-landingpage>
