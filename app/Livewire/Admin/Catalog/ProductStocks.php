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
use Illuminate\Support\Facades\DB;
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

    // stock-in modal
    public bool $stockInModal = false;
    public ?int $stockInVariantId = null;
    public string $stockInLabel = '';
    public string $stockInQuantity = '';
    public string $stockInBatchNo = '';
    public string $stockInExpiryDate = '';
    public string $stockInPurchasePrice = '';
    public string $stockInNote = '';

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

        if ($this->inventoryEnabled()) {
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
        } else {
            $this->writeOwnStock($product, $variant, fn (float $before) => (float) $this->adjustQuantity);
        }

        $this->adjustModal = false;

        // Parent editor holds its own copy of stock_quantity/stock_status for
        // simple products — refresh it so a later "Save Changes" doesn't write
        // the stale values back over this adjustment.
        $this->dispatch('product-stock-updated');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock updated']);
    }

    public function openStockInModal(?int $variantId = null): void
    {
        $product = Product::findOrFail($this->productId);
        $variant = $variantId ? $product->variants()->findOrFail($variantId) : null;

        $this->stockInVariantId = $variant?->id;
        $this->stockInLabel = $variant ? $this->variantLabel($variant) : $product->name;
        $this->stockInQuantity = '';
        $this->stockInBatchNo = '';
        $this->stockInExpiryDate = '';
        $this->stockInPurchasePrice = '';
        $this->stockInNote = '';
        $this->resetValidation();
        $this->stockInModal = true;
    }

    /**
     * Receives new stock on top of the current balance. Inventory module on:
     * logged as a "purchase" movement like the Inventory → Stock In page, via
     * stockInBatch() when a batch number is given (expiry/cost tracked for
     * FEFO), else a plain batch-less increase. Inventory module off: just
     * bumps the item's own stock_quantity — no inventory service or ledger.
     */
    public function saveStockIn(): void
    {
        $inventoryEnabled = $this->inventoryEnabled();

        $this->validate($inventoryEnabled ? [
            'stockInQuantity'      => 'required|numeric|min:0.001',
            'stockInBatchNo'       => 'nullable|string|max:100|required_with:stockInExpiryDate',
            'stockInExpiryDate'    => 'nullable|date',
            'stockInPurchasePrice' => 'nullable|numeric|min:0',
            'stockInNote'          => 'nullable|string|max:255',
        ] : [
            'stockInQuantity'      => 'required|numeric|min:0.001',
        ], [
            'stockInBatchNo.required_with' => 'A batch number is needed to track an expiry date.',
        ]);

        $product = Product::findOrFail($this->productId);
        $variant = $this->stockInVariantId ? $product->variants()->findOrFail($this->stockInVariantId) : null;

        if (! $inventoryEnabled) {
            $this->writeOwnStock($product, $variant, fn (float $before) => $before + (float) $this->stockInQuantity);
        } elseif ($this->stockInBatchNo !== '') {
            app(StockService::class)->stockInBatch(
                $product,
                $variant,
                $this->stockInBatchNo,
                (float) $this->stockInQuantity,
                expiryDate: $this->stockInExpiryDate ?: null,
                purchasePrice: $this->stockInPurchasePrice !== '' ? (float) $this->stockInPurchasePrice : null,
                reference: $variant ?? $product,
                note: $this->stockInNote !== '' ? $this->stockInNote : 'Stock in via product editor',
            );
        } else {
            app(StockService::class)->increase(
                $product,
                $variant,
                (float) $this->stockInQuantity,
                'purchase',
                reference: $variant ?? $product,
                note: $this->stockInNote !== '' ? $this->stockInNote : 'Stock in via product editor',
            );
        }

        $this->stockInModal = false;

        $this->dispatch('product-stock-updated');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock added']);
    }

    protected function inventoryEnabled(): bool
    {
        return (bool) Setting::get('inventory_enabled', false, 'modules');
    }

    /**
     * Inventory module off: plain stock management on the item's own
     * stock_quantity (variant's if given, else the product's) — no
     * StockService, no movement ledger. A simple product's stock_status
     * follows the balance across zero (out_of_stock <-> in_stock;
     * low_stock/backorder are left to the admin), since that's what the
     * storefront reads for simple products.
     *
     * @param  \Closure(float): float  $resolveAfter
     */
    protected function writeOwnStock(Product $product, ?ProductVariant $variant, \Closure $resolveAfter): void
    {
        DB::transaction(function () use ($product, $variant, $resolveAfter) {
            $locked = $variant
                ? ProductVariant::query()->whereKey($variant->id)->lockForUpdate()->firstOrFail()
                : Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $after = max(0.0, $resolveAfter((float) $locked->stock_quantity));
            $changes = ['stock_quantity' => $after];

            if (! $variant) {
                if ($after <= 0 && $locked->stock_status === 'in_stock') {
                    $changes['stock_status'] = 'out_of_stock';
                } elseif ($after > 0 && $locked->stock_status === 'out_of_stock') {
                    $changes['stock_status'] = 'in_stock';
                }
            }

            $locked->update($changes);
        });
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

        // The movement ledger belongs to the Inventory module — not shown
        // (or read) while it's off.
        $movements = $inventoryEnabled
            ? InventoryStockMovement::query()
                ->with('variant', 'createdBy')
                ->where('product_id', $product->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get()
            : collect();

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
