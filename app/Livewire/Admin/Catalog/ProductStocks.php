<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\Product\ProductType;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Stocks tab of the product editor. Shows the item-level balance that
 * StockService actually sells from — one row for a simple product, one row
 * per variant for a variable product — read from inventory_stocks at the
 * default warehouse while the Inventory module is on, or from the items' own
 * stock_quantity columns while it's off. Combo products carry no stock of
 * their own (derived from components), so the tab isn't offered for them.
 */
class ProductStocks extends Component
{
    #[Locked]
    public int $productId;

    /** The product type currently selected (possibly unsaved) in the parent editor. */
    #[Locked]
    public string $pendingType = 'simple';

    // adjust modal
    public bool $adjustModal = false;
    public ?int $adjustingVariantId = null;
    public string $adjustLabel = '';
    public string $adjustQuantity = '';
    public string $adjustNote = '';

    public function mount(int $productId, string $pendingType): void
    {
        $this->productId = $productId;
        $this->pendingType = $pendingType;
    }

    public function openAdjustModal(?int $variantId = null): void
    {
        $product = Product::findOrFail($this->productId);
        $variant = $variantId ? $product->variants()->findOrFail($variantId) : null;

        $this->adjustingVariantId = $variant?->id;
        $this->adjustLabel = $variant ? $this->variantLabel($variant) : $product->name;
        $this->adjustQuantity = (string) $this->onHand($product, $variant);
        $this->adjustNote = '';
        $this->resetValidation();
        $this->adjustModal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustQuantity' => 'required|numeric|min:0',
            'adjustNote'     => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($this->productId);
        $variant = $this->adjustingVariantId ? $product->variants()->findOrFail($this->adjustingVariantId) : null;

        try {
            app(StockService::class)->setAbsolute(
                $product,
                $variant,
                (float) $this->adjustQuantity,
                reference: $variant ?? $product,
                note: $this->adjustNote !== '' ? $this->adjustNote : 'Set via product editor (Stocks tab)',
            );
        } catch (InsufficientStockException $e) {
            $this->addError('adjustQuantity', $e->getMessage());
            return;
        }

        $this->adjustModal = false;

        // Parent editor holds its own copy of stock_quantity/stock_status for
        // simple products — refresh it so a later "Save Changes" doesn't write
        // the stale values back over this adjustment.
        $this->dispatch('product-stock-updated');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock updated']);
    }

    protected function inventoryEnabled(): bool
    {
        return ! app(StockService::class)->usesOwnStock();
    }

    /** Physical quantity for one item — what the adjust modal sets absolutely. */
    protected function onHand(Product $product, ?ProductVariant $variant): float
    {
        if (! $this->inventoryEnabled()) {
            return (float) ($variant ?? $product)->stock_quantity;
        }

        return (float) InventoryStock::query()
            ->where('warehouse_id', Warehouse::default()->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->value('quantity');
    }

    protected function variantLabel(ProductVariant $variant): string
    {
        $options = $variant->options_map;

        return $options ? implode(' / ', $options) : $variant->sku;
    }

    /**
     * One row per stock-carrying item of this product. With the Inventory
     * module off there are no bookings (see StockService), so booked is 0 and
     * available equals on hand.
     */
    protected function buildRows(Product $product, int $lowStockThreshold): Collection
    {
        $inventoryEnabled = $this->inventoryEnabled();

        $items = $product->product_type === ProductType::VARIABLE
            ? $product->variants()
                ->with('values.productAttributeValue.attributeValue.attribute')
                ->orderBy('sort_order')
                ->get()
            : collect([null]);

        $stocks = $inventoryEnabled
            ? InventoryStock::query()
                ->where('warehouse_id', Warehouse::default()->id)
                ->where('product_id', $product->id)
                ->get()
                ->keyBy(fn (InventoryStock $s) => (int) $s->variant_id)
            : collect();

        return $items->map(function (?ProductVariant $variant) use ($product, $stocks, $inventoryEnabled, $lowStockThreshold) {
            if ($inventoryEnabled) {
                $stock = $stocks->get((int) $variant?->id);
                $onHand = (float) ($stock?->quantity ?? 0);
                $booked = (float) ($stock?->booked_quantity ?? 0);
            } else {
                $onHand = (float) ($variant ?? $product)->stock_quantity;
                $booked = 0.0;
            }

            $reorderLevel = $variant ? (float) $variant->reorder_level : 0.0;

            return (object) [
                'variant_id' => $variant?->id,
                'label'      => $variant ? $this->variantLabel($variant) : $product->name,
                'sku'        => $variant ? $variant->sku : $product->code,
                'status'     => $variant?->status,
                'on_hand'    => $onHand,
                'booked'     => $booked,
                'available'  => max(0.0, $onHand - $booked),
                'threshold'  => $reorderLevel > 0 ? $reorderLevel : (float) $lowStockThreshold,
            ];
        });
    }

    public function render(): mixed
    {
        $product = Product::findOrFail($this->productId);
        $lowStockThreshold = (int) Setting::get('low_stock_threshold', 5, 'inventory');
        $inventoryEnabled = $this->inventoryEnabled();

        $rows = $product->product_type === ProductType::COMBO
            ? collect()
            : $this->buildRows($product, $lowStockThreshold);

        $movements = InventoryStockMovement::query()
            ->with('variant', 'createdBy')
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('livewire.admin.catalog.product-stocks', [
            'product'          => $product,
            'savedType'        => $product->product_type->value,
            'inventoryEnabled' => $inventoryEnabled,
            'warehouse'        => $inventoryEnabled ? Warehouse::default() : null,
            'rows'             => $rows,
            'totalOnHand'      => $rows->sum('on_hand'),
            'totalBooked'      => $rows->sum('booked'),
            'totalAvailable'   => $rows->sum('available'),
            'lowStockCount'    => $rows->filter(fn ($r) => $r->available > 0 && $r->available <= $r->threshold)->count(),
            'outOfStockCount'  => $rows->filter(fn ($r) => $r->available <= 0)->count(),
            'movements'        => $movements,
        ]);
    }
}
