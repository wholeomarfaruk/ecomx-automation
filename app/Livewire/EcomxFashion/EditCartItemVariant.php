<?php

namespace App\Livewire\EcomxFashion;

use App\Models\CartItem;
use Livewire\Component;

/**
 * Small per-cart-item component powering the pencil-icon "edit variant"
 * modal in the cart drawer (livewire.ecomx-fashion.cart-manager). Only
 * loads/renders anything when the item's product actually has variants —
 * mirrors App\Livewire\EcomxFashion\Product::loadRealVariants() so the same
 * colour swatch / size grid markup and variantMatrix lookup can be reused.
 * The actual DB write happens in CartManager (kept as the single owner of
 * cart mutations) via the `cart-update-item` event this dispatches.
 */
class EditCartItemVariant extends Component
{
    public int $cartItemId;
    public int $productId;
    public string $productName = '';
    public ?string $productImage = null;

    public bool $hasVariants = false;
    public array $colors = [];
    public array $sizes = [];
    public array $variantMatrix = [];

    public string $selectedColor = '';
    public string $selectedSize = '';
    public int $qty = 1;

    public function mount(CartItem $cartItem): void
    {
        $this->cartItemId = $cartItem->id;
        $this->productId = $cartItem->product_id;
        $this->qty = $cartItem->quantity;

        $product = $cartItem->product;
        if (! $product) {
            return;
        }

        $this->productName = $product->name;
        $this->productImage = $product->featured_image;

        $currentOptions = $cartItem->variant?->options_map ?? [];
        $this->selectedColor = $currentOptions['Color'] ?? '';
        $this->selectedSize = $currentOptions['Size'] ?? '';

        $this->loadVariants($product);
    }

    private function loadVariants($product): void
    {
        $variants = $product->variants()->with('values.productAttributeValue.attributeValue.attribute')->where('status', 'active')->get();

        if ($variants->isEmpty()) {
            return;
        }

        $colorValues = [];
        $sizeValues = [];

        foreach ($variants as $variant) {
            foreach ($variant->values as $variantValue) {
                $av = $variantValue->productAttributeValue?->attributeValue;
                if (! $av) {
                    continue;
                }

                $attrName = $av->attribute?->name;
                if ($attrName === 'Color' && ! isset($colorValues[$av->id])) {
                    $colorValues[$av->id] = $av;
                } elseif ($attrName === 'Size' && ! isset($sizeValues[$av->id])) {
                    $sizeValues[$av->id] = $av;
                }
            }
        }

        if (empty($colorValues) && empty($sizeValues)) {
            return;
        }

        $this->hasVariants = true;

        if (! empty($colorValues)) {
            $this->colors = collect($colorValues)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($v) => ['name' => $v->value, 'hex' => $v->swatch_value ?: '#CCCCCC'])
                ->all();
        }

        if (! empty($sizeValues)) {
            $this->sizes = collect($sizeValues)
                ->sortBy('sort_order')
                ->values()
                ->pluck('value')
                ->all();
        }

        foreach ($variants as $variant) {
            $colorName = null;
            $sizeName = null;
            foreach ($variant->values as $variantValue) {
                $av = $variantValue->productAttributeValue?->attributeValue;
                if (! $av) {
                    continue;
                }

                if ($av->attribute?->name === 'Color') {
                    $colorName = $av->value;
                } elseif ($av->attribute?->name === 'Size') {
                    $sizeName = $av->value;
                }
            }

            $key = ($colorName ?? '*') . '|' . ($sizeName ?? '*');
            $this->variantMatrix[$key] = [
                'variantId' => $variant->id,
                'stock' => $variant->stock_quantity,
            ];
        }
    }

    public function pickColor(string $name): void
    {
        $this->selectedColor = $name;
    }

    public function pickSize(string $value): void
    {
        $this->selectedSize = $value;
    }

    public function save(): void
    {
        $colorKey = ! empty($this->colors) ? ($this->selectedColor ?: '*') : '*';
        $sizeKey = ! empty($this->sizes) ? ($this->selectedSize ?: '*') : '*';
        $variantId = $this->variantMatrix[$colorKey . '|' . $sizeKey]['variantId'] ?? null;

        if ($this->hasVariants && ! $variantId) {
            $this->dispatch('notify', type: 'error', message: 'Please choose a valid variant.');
            return;
        }

        $this->dispatch('cart-update-item', cartItemId: $this->cartItemId, variantId: $variantId, qty: $this->qty);
        $this->dispatch('close-edit-cart-item-' . $this->cartItemId);
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.edit-cart-item-variant');
    }
}
