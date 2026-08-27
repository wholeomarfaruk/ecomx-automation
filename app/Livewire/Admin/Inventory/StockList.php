<?php

namespace App\Livewire\Admin\Inventory;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\StockService;
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
    public $adjustQuantity = '';
    public $adjustNote = '';

    public function mount(): void
    {
        $this->lowStockThreshold = (int) Setting::get('low_stock_threshold', 5, 'inventory');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilter(): void { $this->resetPage(); }

    protected function buildQuery()
    {
        // Effective threshold: the variant's own reorder_level if set (> 0),
        // otherwise the store-wide low_stock_threshold setting.
        $effectiveThreshold = 'COALESCE(NULLIF(reorder_level, 0), ' . (int) $this->lowStockThreshold . ')';

        return ProductVariant::query()
            ->with('product')
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('sku', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filter === 'low', fn ($q) => $q->where('stock_quantity', '>', 0)->whereRaw("stock_quantity <= {$effectiveThreshold}"))
            ->when($this->filter === 'out', fn ($q) => $q->where('stock_quantity', '<=', 0))
            ->orderBy('stock_quantity');
    }

    public function openAdjustModal(int $variantId): void
    {
        $variant = ProductVariant::findOrFail($variantId);

        $this->adjustingVariantId = $variantId;
        $this->adjustQuantity = (string) $variant->stock_quantity;
        $this->adjustNote = '';
        $this->resetValidation();
        $this->adjustModal = true;
    }

    public function saveAdjustment(): void
    {
        $this->validate([
            'adjustQuantity' => 'required|numeric|min:0',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($this->adjustingVariantId);

        try {
            app(StockService::class)->setAbsolute(
                $variant->product,
                $variant,
                (float) $this->adjustQuantity,
                reference: $variant,
                note: $this->adjustNote !== '' ? $this->adjustNote : 'Manual adjustment from Inventory',
            );
        } catch (InsufficientStockException $e) {
            $this->addError('adjustQuantity', $e->getMessage());
            return;
        }

        $this->adjustModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Stock updated']);
    }

    public function render(): mixed
    {
        $variants = $this->buildQuery()->paginate(20);

        $effectiveThreshold = 'COALESCE(NULLIF(reorder_level, 0), ' . (int) $this->lowStockThreshold . ')';

        return view('livewire.admin.inventory.stock-list', [
            'variants' => $variants,
            'lowStockThreshold' => $this->lowStockThreshold,
            'totalVariants' => ProductVariant::count(),
            'lowStockCount' => ProductVariant::where('stock_quantity', '>', 0)->whereRaw("stock_quantity <= {$effectiveThreshold}")->count(),
            'outOfStockCount' => ProductVariant::where('stock_quantity', '<=', 0)->count(),
            'totalUnits' => ProductVariant::sum('stock_quantity'),
        ])->layout('layouts.admin.admin');
    }
}
