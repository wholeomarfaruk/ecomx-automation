<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductEdit extends Component
{
    use WithMediaPicker;

    public int $productId;
    public string $activeTab = 'general';

    // General
    public string $name              = '';
    public string $slug              = '';
    public string $code              = '';
    public string $shortDescription  = '';
    public string $description       = '';
    public string $brandId           = '';
    public array  $categoryIds       = [];
    public string $status            = 'draft';
    public bool   $featured          = false;
    public string $stockStatus       = 'in_stock';

    // Pricing
    public string $price          = '';
    public string $salePrice      = '';
    public string $purchasePrice  = '';

    // Media
    public $featuredImageId = null;
    public array $imageIds  = [];
    public array $videoIds  = [];

    // Shipping
    public string $weight = '';
    public string $length = '';
    public string $width  = '';
    public string $height = '';

    // SEO
    public $metaImageId          = null;
    public string $metaTitle       = '';
    public string $metaDescription = '';
    public string $metaKeywords    = '';

    public function mount(int $id): void
    {
        $product = Product::with('categories')->findOrFail($id);

        $this->productId          = $product->id;
        $this->name                = $product->name;
        $this->slug                = $product->slug;
        $this->code                = $product->code;
        $this->shortDescription    = $product->short_description ?? '';
        $this->description         = $product->description ?? '';
        $this->brandId             = (string) ($product->brand_id ?? '');
        $this->categoryIds         = $product->categories->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->status               = $product->status;
        $this->featured            = $product->featured;
        $this->stockStatus         = $product->stock_status;

        $this->price           = $product->price !== null ? (string) $product->price : '';
        $this->salePrice       = $product->sale_price !== null ? (string) $product->sale_price : '';
        $this->purchasePrice   = $product->purchase_price !== null ? (string) $product->purchase_price : '';

        $this->featuredImageId = $product->featured_image_id;
        $this->imageIds         = $product->image_ids ?? [];
        $this->videoIds         = $product->video_ids ?? [];

        $this->weight = $product->weight !== null ? (string) $product->weight : '';
        $this->length = $product->length !== null ? (string) $product->length : '';
        $this->width  = $product->width !== null ? (string) $product->width : '';
        $this->height = $product->height !== null ? (string) $product->height : '';

        $this->metaImageId     = $product->meta_image_id;
        $this->metaTitle       = $product->meta_title ?? '';
        $this->metaDescription = $product->meta_description ?? '';
        $this->metaKeywords    = $product->meta_keywords ?? '';
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function rules(): array
    {
        return [
            'name'              => 'required|string|max:150',
            'slug'              => 'required|string|max:170|unique:products,slug,' . $this->productId,
            'code'              => 'required|string|max:100|unique:products,code,' . $this->productId,
            'shortDescription'  => 'nullable|string',
            'description'       => 'nullable|string',
            'brandId'           => 'nullable|integer|exists:brands,id',
            'categoryIds.*'     => 'integer|exists:categories,id',
            'status'            => 'required|in:draft,active,inactive,archived',
            'stockStatus'       => 'required|in:in_stock,out_of_stock,low_stock,backorder',

            'price'             => 'nullable|numeric|min:0',
            'salePrice'         => 'nullable|numeric|min:0',
            'purchasePrice'     => 'nullable|numeric|min:0',

            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width'  => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',

            'metaTitle'       => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string',
            'metaKeywords'    => 'nullable|string',
        ];
    }

    public function updatedName(string $value): void
    {
        // Keep slug independent once product exists; only suggest if slug is empty.
        if ($this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function removeGalleryImage(int|string $id): void
    {
        $this->imageIds = array_values(array_filter($this->imageIds, fn($i) => $i != $id));
    }

    public function removeVideo(int|string $id): void
    {
        $this->videoIds = array_values(array_filter($this->videoIds, fn($i) => $i != $id));
    }

    public function save(): void
    {
        $this->validate();

        $product = Product::findOrFail($this->productId);

        $product->update([
            'name'               => $this->name,
            'slug'               => $this->slug,
            'code'               => $this->code,
            'short_description'  => $this->shortDescription ?: null,
            'description'        => $this->description ?: null,
            'brand_id'           => $this->brandId ?: null,
            'status'             => $this->status,
            'featured'           => $this->featured,
            'stock_status'       => $this->stockStatus,

            'price'              => $this->price !== '' ? $this->price : null,
            'sale_price'         => $this->salePrice !== '' ? $this->salePrice : null,
            'purchase_price'     => $this->purchasePrice !== '' ? $this->purchasePrice : null,

            'featured_image_id'  => $this->featuredImageId ?: null,
            'image_ids'          => ! empty($this->imageIds) ? $this->imageIds : null,
            'video_ids'          => ! empty($this->videoIds) ? $this->videoIds : null,

            'weight' => $this->weight !== '' ? $this->weight : null,
            'length' => $this->length !== '' ? $this->length : null,
            'width'  => $this->width !== '' ? $this->width : null,
            'height' => $this->height !== '' ? $this->height : null,

            'meta_image_id'     => $this->metaImageId ?: null,
            'meta_title'        => $this->metaTitle ?: null,
            'meta_description'  => $this->metaDescription ?: null,
            'meta_keywords'     => $this->metaKeywords ?: null,
        ]);

        $product->categories()->sync($this->categoryIds);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->event('updated')
            ->log("Product \"{$product->name}\" was updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product saved successfully']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.catalog.product-edit', [
            'brands'     => Brand::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'parent_id']),
        ])->layout('layouts.admin.admin');
    }
}
