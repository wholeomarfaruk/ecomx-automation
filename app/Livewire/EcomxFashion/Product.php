<?php

namespace App\Livewire\EcomxFashion;

use App\Marketing\Events\ViewContent;
use App\Marketing\Services\MarketingEventService;
use App\Models\Device;
use App\Models\Product as ProductModel;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Shell for the ecomx-fashion product page: breadcrumb + title only. The
 * gallery/buy-box, reviews, and related-products sections each render as
 * their own #[Lazy] Livewire component (see ProductGalleryBuyBox,
 * ProductReviewsSlider, ProductRelatedCarousel) so their queries don't block
 * first paint — each shows a skeleton until it loads.
 */
#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Product extends Component
{
    public int $productId;

    public array $product = [
        'name' => '',
        'cat' => '',
    ];

    public array $marketingEvents = [];

    public function mount(?string $slug = null): void
    {
        $p = ProductModel::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $this->productId = $p->id;
        $this->product = [
            'name' => $p->name,
            'cat' => $p->categories->first()->name ?? '',
        ];

        $this->recordViewContent($p);
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
        return view('ecomx-fashion.livewire.product');
    }
}
