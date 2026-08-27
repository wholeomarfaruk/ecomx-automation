<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\LandingPageEngine\TemplateComponentRegistrar;
use App\Models\LandingPage;

class LandingPagePreviewController extends Controller
{
    /**
     * Resolves the page's template's root component tag via
     * TemplateComponentRegistrar (the same alias
     * App\Livewire\LandingPageEngine\LandingPageRenderer resolves for the
     * public route) and @livewire()s it inside
     * landingpage-preview.blade.php — a controller-only wrapper that
     * composes the shared landing-page layout as a genuine Blade
     * component invocation, since this plain controller has no Livewire
     * #[Layout(...)] attribute to rely on. A draft/unpublished page
     * (findOrFail, not ::published()) is fine here since this route is
     * auth-gated by the existing panel:admin middleware wrapping
     * routes/admin.php.
     */
    public function show(int $id, TemplateComponentRegistrar $registrar): mixed
    {
        $landingPage = LandingPage::findOrFail($id);
        $template = $landingPage->template;

        return view('livewire.landingpage-engine.landingpage-preview', [
            'componentTag' => $registrar->rootComponentAlias($template),
            'landingPageId' => $landingPage->id,
            'title' => $landingPage->seo['meta_title'] ?? $landingPage->title,
            'metaDescription' => $landingPage->seo['meta_description'] ?? null,
            'metaImage' => $landingPage->seo['meta_image'] ?? null,
        ]);
    }
}
