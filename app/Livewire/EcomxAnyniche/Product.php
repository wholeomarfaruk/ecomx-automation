<?php

namespace App\Livewire\EcomxAnyniche;

use App\Marketing\Events\ViewContent;
use App\Marketing\Services\MarketingEventService;
use App\Models\Device;
use App\Models\Product as ProductModel;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Shell for the ecomx-anyniche product page: breadcrumb + title only. The
 * gallery/buy-box, reviews, and related-products sections each render as
 * their own #[Lazy] Livewire component (see ProductGalleryBuyBox,
 * ProductReviewsSlider, ProductRelatedCarousel) so their queries don't block
 * first paint — each shows a skeleton until it loads.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class Product extends Component
{
    public int $productId;

    public array $product = [
        'name' => '',
        'cat' => '',
        'catSlug' => '',
    ];

    public array $marketingEvents = [];

    public ?string $pageTitle = null;
    public ?string $pageMetaDescription = null;
    public ?string $pageMetaImage = null;

    public function mount(?string $slug = null): void
    {
        $p = ProductModel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $firstCategory = $p->categories->first();

        $this->productId = $p->id;
        $this->product = [
            'name' => $p->name,
            'cat' => $firstCategory->name ?? '',
            'catSlug' => $firstCategory->slug ?? '',
        ];

        $this->buildSeo($p);
        $this->recordViewContent($p);
    }

    /**
     * Falls back to the product's own name/short description/featured image
     * whenever meta_title/meta_description/meta_image_id aren't set in
     * Admin > Products > SEO — the product page must never show a blank or
     * broken <title>/description just because SEO fields were left empty.
     */
    private function buildSeo(ProductModel $p): void
    {
        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        $this->pageTitle = $p->meta_title ?: "{$p->name} — {$siteName}";
        $this->pageMetaDescription = $p->meta_description ?: ($p->short_description ?: null);

        try {
            $this->pageMetaImage = $p->meta_image_id
                ? file_path($p->meta_image_id)
                : ($p->featured_image_id ? file_path($p->featured_image_id) : null);
        } catch (\Throwable $e) {
            $this->pageMetaImage = null;
        }
    }

    private function recordViewContent(ProductModel $product): void
    {
        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $event = ViewContent::create(
            contentId: $product->id,
            contentName: $product->name,
            contentType: 'product',
            value: (float) ($product->sale_price ?? $product->price),
            currency: 'BDT',
        );

        $result = app(MarketingEventService::class)->recordForCurrentRequest(
            event: $event,
            device: $device,
            customer: auth()->check() ? auth()->user()->customer : null,
        );

        $this->marketingEvents[] = $result['browserPayload'];
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.product')
            ->layout('ecomx-anyniche.layouts.ecomx_anyniche', array_filter([
                'title' => $this->pageTitle,
                'metaDescription' => $this->pageMetaDescription,
                'metaImage' => $this->pageMetaImage,
            ]));
    }
}
