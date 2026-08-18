<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
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
    public string $productType       = 'simple';
    public bool   $comboAllowed      = false;
    public bool   $giftAllowed       = false;

    // Combo
    /** @var array<int, array{product_id: string, variant_id: string, allow_variant: bool, product_label: string, quantity: string}> */
    public array $comboItems = [];
    public string $comboProductSearch = '';

    // Gift
    /** @var array<int, array{gift_product_id: string, product_label: string, quantity: string}> */
    public array $giftItems = [];
    public string $giftProductSearch = '';

    // Pricing
    public string $price          = '';
    public string $salePrice      = '';
    public string $purchasePrice  = '';
    public string $comboPrice     = '';

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
        $product = Product::with('categories', 'comboItems.product', 'comboItems.variant', 'gifts.giftProduct')->findOrFail($id);

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
        $this->productType         = $product->product_type->value;
        $this->comboAllowed        = $product->combo_allowed;
        $this->giftAllowed         = $product->gift_allowed;

        $this->comboItems = $product->comboItems->map(fn ($item) => [
            'product_id'    => (string) $item->product_id,
            'variant_id'    => $item->variant_id ? (string) $item->variant_id : '',
            'allow_variant' => $item->allow_variant,
            'product_label' => $item->product->name . ($item->variant ? " ({$item->variant->sku})" : ''),
            'quantity'      => (string) $item->quantity,
        ])->all();

        $this->giftItems = $product->gifts->map(fn ($item) => [
            'gift_product_id' => (string) $item->gift_product_id,
            'product_label'   => $item->giftProduct->name,
            'quantity'        => (string) $item->quantity,
        ])->all();

        $this->price           = $product->price !== null ? (string) $product->price : '';
        $this->salePrice       = $product->sale_price !== null ? (string) $product->sale_price : '';
        $this->purchasePrice   = $product->purchase_price !== null ? (string) $product->purchase_price : '';
        $this->comboPrice      = $product->combo_price !== null ? (string) $product->combo_price : '';

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
            'productType'       => 'required|in:simple,variable,combo',
            'comboAllowed'      => 'boolean',
            'giftAllowed'       => 'boolean',

            'comboItems'                    => 'required_if:productType,combo|array',
            'comboItems.*.product_id'       => 'required|integer|exists:products,id',
            'comboItems.*.variant_id'       => 'nullable|integer|exists:product_variants,id',
            'comboItems.*.allow_variant'    => 'boolean',
            'comboItems.*.quantity'         => 'required|numeric|min:0.001',

            'giftItems'                     => 'required_if:giftAllowed,true|array',
            'giftItems.*.gift_product_id'   => 'required|integer|exists:products,id',
            'giftItems.*.quantity'          => 'required|numeric|min:0.001',

            'price'             => 'nullable|numeric|min:0',
            'salePrice'         => 'nullable|numeric|min:0',
            'purchasePrice'     => 'nullable|numeric|min:0',
            'comboPrice'        => 'nullable|numeric|min:0',

            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width'  => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',

            'metaTitle'       => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string',
            'metaKeywords'    => 'nullable|string',
        ];
    }

    public function updatedProductType(string $value): void
    {
        if ($value === 'combo') {
            $this->comboAllowed  = false;
            $this->comboPrice    = '';
            $this->purchasePrice = '';
        } elseif ($this->activeTab === 'combo') {
            $this->activeTab = 'general';
        }
    }

    public function updatedGiftAllowed(bool $value): void
    {
        if (! $value) {
            $this->giftItems = [];

            if ($this->activeTab === 'gift') {
                $this->activeTab = 'general';
            }
        }
    }

    public function updatedName(string $value): void
    {
        // Keep slug independent once product exists; only suggest if slug is empty.
        if ($this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function addComboItem(int $productId): void
    {
        $product = Product::active()
            ->where('combo_allowed', true)
            ->where('product_type', '!=', 'combo')
            ->where('id', '!=', $this->productId)
            ->find($productId);

        if (! $product) {
            return;
        }

        $this->comboItems[] = [
            'product_id'    => (string) $product->id,
            'variant_id'    => '',
            'allow_variant' => false,
            'product_label' => $product->name,
            'quantity'      => '1',
        ];

        $this->comboProductSearch = '';
    }

    public function removeComboItem(int $index): void
    {
        unset($this->comboItems[$index]);
        $this->comboItems = array_values($this->comboItems);
    }

    public function updatedComboItems($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if ($field === 'allow_variant' && $index !== null && $this->comboItems[$index]['allow_variant']) {
            $this->comboItems[$index]['variant_id'] = '';
        }
    }

    public function addGiftItem(int $productId): void
    {
        $product = Product::active()
            ->where('id', '!=', $this->productId)
            ->find($productId);

        if (! $product) {
            return;
        }

        $this->giftItems[] = [
            'gift_product_id' => (string) $product->id,
            'product_label'   => $product->name,
            'quantity'        => '1',
        ];

        $this->giftProductSearch = '';
    }

    public function removeGiftItem(int $index): void
    {
        unset($this->giftItems[$index]);
        $this->giftItems = array_values($this->giftItems);
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
            'product_type'       => $this->productType,
            'combo_allowed'      => $this->comboAllowed,
            'gift_allowed'       => $this->giftAllowed,

            'price'              => $this->price !== '' ? $this->price : null,
            'sale_price'         => $this->salePrice !== '' ? $this->salePrice : null,
            'purchase_price'     => $this->purchasePrice !== '' ? $this->purchasePrice : null,
            'combo_price'        => $this->comboPrice !== '' ? $this->comboPrice : null,

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

        $product->comboItems()->delete();

        if ($this->productType === 'combo') {
            foreach ($this->comboItems as $i => $item) {
                $product->comboItems()->create([
                    'product_id'    => $item['product_id'],
                    'variant_id'    => $item['variant_id'] ?: null,
                    'allow_variant' => $item['allow_variant'] ?? false,
                    'quantity'      => $item['quantity'],
                    'sort_order'    => $i,
                ]);
            }
        }

        $product->gifts()->delete();

        if ($this->giftAllowed) {
            foreach ($this->giftItems as $item) {
                $product->gifts()->create([
                    'gift_product_id' => $item['gift_product_id'],
                    'quantity'        => $item['quantity'],
                ]);
            }
        }

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->event('updated')
            ->log("Product \"{$product->name}\" was updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product saved successfully']);
    }

    public function render(): mixed
    {
        $comboProductOptions = collect();
        if ($this->comboProductSearch !== '') {
            $comboProductOptions = Product::active()
                ->where('combo_allowed', true)
                ->where('product_type', '!=', 'combo')
                ->where('id', '!=', $this->productId)
                ->where('name', 'like', "%{$this->comboProductSearch}%")
                ->limit(10)
                ->get();
        }

        $comboProductIds = collect($this->comboItems)->pluck('product_id')->filter()->unique();
        $comboVariantOptions = ProductVariant::active()
            ->whereIn('product_id', $comboProductIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_id');

        $giftProductOptions = collect();
        if ($this->giftProductSearch !== '') {
            $giftProductOptions = Product::active()
                ->where('id', '!=', $this->productId)
                ->where('name', 'like', "%{$this->giftProductSearch}%")
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.catalog.product-edit', [
            'brands'               => Brand::orderBy('name')->get(['id', 'name']),
            'categories'           => Category::orderBy('name')->get(['id', 'name', 'parent_id']),
            'comboProductOptions'  => $comboProductOptions,
            'comboVariantOptions'  => $comboVariantOptions,
            'giftProductOptions'   => $giftProductOptions,
        ])->layout('layouts.admin.admin');
    }
}
