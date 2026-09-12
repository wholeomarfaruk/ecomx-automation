<?php

namespace App\Livewire\Admin\Inventory;

use App\Enums\Product\ProductType;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class StockList extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'all';

    protected string $paginationTheme = 'tailwind';

    public int $lowStockThreshold = 5;

    // adjust modal
    public bool $adjustModal = false;
    public ?int $adjustingVariantId = null;
    public ?int $adjustingProductId = null;
    public $adjustQuantity = '';
    public $adjustNote = '';

    public function mount(): void
    {
        $this->lowStockThreshold = (int) Setting::get('low_stock_threshold', 5, 'inventory');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilter(): void { $this->resetPage(); }

    /**
     * A unified, filterable/sortable row set of everything that carries its
     * own trackable stock: one row per variant for variable products, plus
     * one row per simple (variant-less) product, read from inventory_stocks
     * at the default warehouse since simple products have no stock_quantity
     * column of their own. Combo products are excluded — their stock is
     * derived from their components, not tracked directly.
     */
    protected function buildRows(): Collection
    {
        $effectiveThreshold = fn ($reorderLevel) => $reorderLevel > 0 ? (float) $reorderLevel : (float) $this->lowStockThreshold;

        $variantRows = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($p) => $p->where('product_type', '!=', ProductType::COMBO->value))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('sku', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->get()
            ->map(function (ProductVariant $variant) use ($effectiveThreshold) {
                return (object) [
                    'key' => 'variant-' . $variant->id,
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product->name ?? '—',
                    'sku' => $variant->sku,
                    'quantity' => (float) $variant->stock_quantity,
                    'threshold' => $effectiveThreshold($variant->reorder_level),
                ];
            });

        $warehouseId = Warehouse::default()->id;

        $productRows = Product::query()
            ->where('product_type', ProductType::SIMPLE->value)
            ->whereDoesntHave('variants')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->leftJoin('inventory_stocks', function ($join) use ($warehouseId) {
                $join->on('inventory_stocks.product_id', '=', 'products.id')
                    ->whereNull('inventory_stocks.variant_id')
                    ->where('inventory_stocks.warehouse_id', $warehouseId);
            })
            ->get(['products.id', 'products.name', 'products.code', 'inventory_stocks.quantity'])
            ->map(function ($product) use ($effectiveThreshold) {
                return (object) [
                    'key' => 'product-' . $product->id,
                    'variant_id' => null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->code,
                    'quantity' => (float) ($product->quantity ?? 0),
                    'threshold' => $effectiveThreshold(0),
                ];
            });

        $rows = $variantRows->concat($productRows);

        $rows = match ($this->filter) {
            'low' => $rows->filter(fn ($r) => $r->quantity > 0 && $r->quantity <= $r->threshold),
            'out' => $rows->filter(fn ($r) => $r->quantity <= 0),
            default => $rows,
        };

        return $rows->sortBy('quantity')->values();
    }

    protected function paginateRows(Collection $rows): LengthAwarePaginator
    {
        $page = $this->getPage();
        $perPage = 20;

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage),
            $rows->count(),
            $perPage,
            $page,
            ['pageName' => 'page'],
        );
    }

    public function openAdjustModal(?int $variantId, ?int $productId = null): void
    {
        if ($variantId) {
            $variant = ProductVariant::findOrFail($variantId);
            $this->adjustingVariantId = $variantId;
            $this->adjustingProductId = null;
            $this->adjustQuantity = (string) $variant->stock_quantity;
        } else {
            $this->adjustingVariantId = null;
            $this->adjustingProductId = $productId;
            $this->adjustQuantity = (string) app(StockService::class)->available(Product::findOrFail($productId), null);
        }

        $this->adjustNote = '';
        $this->resetValidation();
        $this->adjustModal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustQuantity' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->adjustingVariantId) {
                $variant = ProductVariant::with('product')->findOrFail($this->adjustingVariantId);

                app(StockService::class)->setAbsolute(
                    $variant->product,
                    $variant,
                    (float) $this->adjustQuantity,
                    reference: $variant,
                    note: $this->adjustNote !== '' ? $this->adjustNote : 'Manual adjustment from Inventory',
                );
            } else {
                $product = Product::findOrFail($this->adjustingProductId);

                app(StockService::class)->setAbsolute(
                    $product,
                    null,
                    (float) $this->adjustQuantity,
                    reference: $product,
                    note: $this->adjustNote !== '' ? $this->adjustNote : 'Manual adjustment from Inventory',
                );
            }
        } catch (InsufficientStockException $e) {
            $this->addError('adjustQuantity', $e->getMessage());
            return;
        }

        $this->adjustModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock updated']);
    }

    public function render(): mixed
    {
        $rows = $this->buildRows();

        return view('livewire.admin.inventory.stock-list', [
            'rows' => $this->paginateRows($rows),
            'lowStockThreshold' => $this->lowStockThreshold,
            'totalVariants' => $rows->count(),
            'lowStockCount' => $rows->filter(fn ($r) => $r->quantity > 0 && $r->quantity <= $r->threshold)->count(),
            'outOfStockCount' => $rows->filter(fn ($r) => $r->quantity <= 0)->count(),
            'totalUnits' => $rows->sum('quantity'),
        ])->layout('layouts.admin.admin');
    }
}
