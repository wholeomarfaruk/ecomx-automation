<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantLink;
use App\Models\ProductVariantValue;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductVariants extends Component
{
    use WithMediaPicker;

    public int $productId;

    public string $newAttributeId = '';

    // variant edit modal
    public bool   $variantModal        = false;
    public ?int   $editingVariantId    = null;
    public string $variantSku          = '';
    public string $variantPrice        = '';
    public string $variantSalePrice    = '';
    public string $variantPurchasePrice = '';
    public string $variantStockQuantity = '0';
    public string $variantStatus       = 'active';
    public array  $variantImageIds     = [];

    // color-link manager (within variant modal)
    public string $linkProductSearch = '';
    public string $linkType          = 'color';

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function addAttribute(): void
    {
        if ($this->newAttributeId === '') {
            return;
        }

        $product = Product::findOrFail($this->productId);

        $exists = $product->productAttributes()->where('attribute_id', $this->newAttributeId)->exists();
        if ($exists) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Attribute already added']);
            return;
        }

        $maxOrder = $product->productAttributes()->count();

        $productAttribute = $product->productAttributes()->create([
            'attribute_id' => $this->newAttributeId,
        ]);

        $product->attributeOrder()->create([
            'product_attribute_id' => $productAttribute->id,
            'sort_order'           => $maxOrder + 1,
        ]);

        $this->newAttributeId = '';
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attribute added to product']);
    }

    public function removeAttribute(int $productAttributeId): void
    {
        ProductAttribute::findOrFail($productAttributeId)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attribute removed. Existing variants using it may need regenerating.']);
    }

    public function reorderAttributes(array $orderedProductAttributeIds): void
    {
        $product = Product::findOrFail($this->productId);

        foreach ($orderedProductAttributeIds as $index => $productAttributeId) {
            $product->attributeOrder()
                ->where('product_attribute_id', $productAttributeId)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function toggleValue(int $productAttributeId, int $attributeValueId): void
    {
        $existing = ProductAttributeValue::where('product_attribute_id', $productAttributeId)
            ->where('attribute_value_id', $attributeValueId)
            ->first();

        if ($existing) {
            $existing->delete();
            return;
        }

        $maxOrder = ProductAttributeValue::where('product_attribute_id', $productAttributeId)->max('sort_order') ?? 0;

        ProductAttributeValue::create([
            'product_attribute_id' => $productAttributeId,
            'attribute_value_id'   => $attributeValueId,
            'sort_order'           => $maxOrder + 1,
        ]);
    }

    public function generateVariants(): void
    {
        $product = Product::with('productAttributes.values.attributeValue', 'productAttributes.attribute')->findOrFail($this->productId);

        $groups = $product->productAttributes
            ->filter(fn($pa) => $pa->values->isNotEmpty())
            ->map(fn($pa) => $pa->values->map(fn($v) => ['product_attribute_value_id' => $v->id, 'label' => $v->attributeValue->value]))
            ->values();

        if ($groups->isEmpty()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Add attributes and select values before generating variants']);
            return;
        }

        // Cartesian product of all value groups.
        $combinations = $groups->reduce(function ($carry, $group) {
            if ($carry === null) {
                return $group->map(fn($item) => [$item]);
            }
            return $carry->flatMap(fn($combo) => $group->map(fn($item) => [...$combo, $item]));
        }, null);

        $existingKeys = ProductVariant::where('product_id', $product->id)->pluck('combination_key')->all();
        $maxOrder = ProductVariant::where('product_id', $product->id)->max('sort_order') ?? 0;
        $created = 0;

        foreach ($combinations as $combo) {
            $valueIds = collect($combo)->pluck('product_attribute_value_id')->sort()->values();
            $key = $valueIds->implode('-');

            if (in_array($key, $existingKeys)) {
                continue;
            }

            $labels = collect($combo)->pluck('label')->implode('-');
            $sku = strtoupper($product->code . '-' . Str::slug($labels, '-'));
            $sku = Str::limit($sku, 190, '');

            $maxOrder++;
            $variant = ProductVariant::create([
                'product_id'       => $product->id,
                'sku'              => $sku,
                'combination_key'  => $key,
                'status'           => 'active',
                'sort_order'       => $maxOrder,
            ]);

            foreach ($combo as $item) {
                ProductVariantValue::create([
                    'product_variant_id'          => $variant->id,
                    'product_attribute_value_id'  => $item['product_attribute_value_id'],
                ]);
            }

            $existingKeys[] = $key;
            $created++;
        }

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $created > 0 ? "{$created} variant(s) generated" : 'All combinations already exist',
        ]);
    }

    public function reorderVariants(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ProductVariant::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function toggleVariantStatus(int $id): void
    {
        $variant = ProductVariant::findOrFail($id);
        $variant->update(['status' => $variant->status === 'active' ? 'inactive' : 'active']);
    }

    public function deleteVariant(int $id): void
    {
        ProductVariant::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Variant deleted']);
    }

    public function editVariant(int $id): void
    {
        $variant = ProductVariant::findOrFail($id);

        $this->editingVariantId       = $variant->id;
        $this->variantSku             = $variant->sku;
        $this->variantPrice           = $variant->price !== null ? (string) $variant->price : '';
        $this->variantSalePrice       = $variant->sale_price !== null ? (string) $variant->sale_price : '';
        $this->variantPurchasePrice   = $variant->purchase_price !== null ? (string) $variant->purchase_price : '';
        $this->variantStockQuantity   = (string) $variant->stock_quantity;
        $this->variantStatus          = $variant->status;
        $this->variantImageIds        = $variant->media()->pluck('media_id')->all();
        $this->linkProductSearch      = '';
        $this->resetValidation();
        $this->variantModal           = true;
    }

    public function updatedVariantImageIds(): void
    {
        // handled on save
    }

    public function saveVariant(): void
    {
        $variant = ProductVariant::findOrFail($this->editingVariantId);

        $this->validate([
            'variantSku'             => 'required|string|max:190|unique:product_variants,sku,' . $variant->id,
            'variantPrice'           => 'nullable|numeric|min:0',
            'variantSalePrice'       => 'nullable|numeric|min:0',
            'variantPurchasePrice'   => 'nullable|numeric|min:0',
            'variantStockQuantity'   => 'required|numeric|min:0',
            'variantStatus'          => 'required|in:active,inactive',
        ]);

        $variant->update([
            'sku'             => $this->variantSku,
            'price'           => $this->variantPrice !== '' ? $this->variantPrice : null,
            'sale_price'      => $this->variantSalePrice !== '' ? $this->variantSalePrice : null,
            'purchase_price'  => $this->variantPurchasePrice !== '' ? $this->variantPurchasePrice : null,
            'stock_quantity'  => $this->variantStockQuantity,
            'status'          => $this->variantStatus,
        ]);

        $variant->media()->delete();
        foreach ($this->variantImageIds as $index => $mediaId) {
            $variant->media()->create([
                'media_id'    => $mediaId,
                'is_primary'  => $index === 0,
                'sort_order'  => $index + 1,
            ]);
        }

        $this->variantModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Variant saved']);
    }

    public function linkToProduct(int $linkedProductId): void
    {
        $variant = ProductVariant::findOrFail($this->editingVariantId);

        $exists = $variant->links()
            ->where('linked_product_id', $linkedProductId)
            ->where('link_type', $this->linkType)
            ->exists();

        if ($exists) {
            return;
        }

        $maxOrder = $variant->links()->max('sort_order') ?? 0;

        ProductVariantLink::create([
            'product_variant_id' => $variant->id,
            'linked_product_id'  => $linkedProductId,
            'link_type'          => $this->linkType,
            'sort_order'         => $maxOrder + 1,
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product linked']);
    }

    public function unlinkProduct(int $linkId): void
    {
        ProductVariantLink::findOrFail($linkId)->delete();
    }

    public function render(): mixed
    {
        $product = Product::with([
            'productAttributes.attribute',
            'productAttributes.values.attributeValue',
            'attributeOrder',
            'variants.values.productAttributeValue.attributeValue.attribute',
            'variants.media',
            'variants.links.linkedProduct',
        ])->findOrFail($this->productId);

        $orderedAttributes = $product->attributeOrder()
            ->with('productAttribute.attribute', 'productAttribute.values.attributeValue')
            ->orderBy('sort_order')
            ->get()
            ->pluck('productAttribute');

        $availableAttributes = Attribute::active()
            ->whereNotIn('id', $product->productAttributes->pluck('attribute_id'))
            ->orderBy('name')
            ->get();

        $editingVariant = $this->editingVariantId
            ? ProductVariant::with('links.linkedProduct')->find($this->editingVariantId)
            : null;

        $linkableProducts = collect();
        if ($this->variantModal && $this->linkProductSearch !== '') {
            $linkableProducts = Product::where('id', '!=', $this->productId)
                ->where(fn($q) => $q->where('name', 'like', "%{$this->linkProductSearch}%")
                    ->orWhere('code', 'like', "%{$this->linkProductSearch}%"))
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.catalog.product-variants', [
            'orderedAttributes'    => $orderedAttributes,
            'availableAttributes'  => $availableAttributes,
            'variants'             => $product->variants,
            'editingVariant'       => $editingVariant,
            'linkableProducts'     => $linkableProducts,
        ]);
    }
}
