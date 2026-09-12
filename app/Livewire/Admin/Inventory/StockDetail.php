<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\File;
use App\Models\InventoryBatch;
use App\Models\InventoryStockMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\StockService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class StockDetail extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /** 'variant' or 'product' — matches the two row kinds on the Stock List. */
    #[Locked]
    public string $type;

    #[Locked]
    public int $id;

    public function mount(string $type, int $id): void
    {
        $this->type = $type;
        $this->id = $id;
    }

    protected function resolveSubject(): array
    {
        if ($this->type === 'variant') {
            $variant = ProductVariant::with('product', 'media')->findOrFail($this->id);

            return [$variant->product, $variant];
        }

        $product = Product::findOrFail($this->id);

        return [$product, null];
    }

    protected function thumbnail(Product $product, ?ProductVariant $variant): ?string
    {
        $primaryMedia = $variant?->media?->firstWhere('is_primary', true) ?? $variant?->media?->first();
        $fileId = $primaryMedia?->media_id ?? $product->featured_image_id;

        if (! $fileId) {
            return null;
        }

        $file = File::with('items')->find($fileId);
        $item = $file?->items->firstWhere('type', 'original');

        return $item ? asset('storage/' . $item->path) : null;
    }

    /**
     * Running after_quantity over time, one point per day — the last
     * movement of each day represents that day's closing balance. Feeds the
     * stock trend line chart.
     */
    protected function trendData(\Illuminate\Support\Collection $movements): array
    {
        $byDay = $movements->sortBy('created_at')->groupBy(fn ($m) => $m->created_at?->format('Y-m-d'));

        $labels = [];
        $balances = [];

        foreach ($byDay as $day => $dayMovements) {
            $labels[] = \Illuminate\Support\Carbon::parse($day)->format('M j');
            $balances[] = (float) $dayMovements->last()->after_quantity;
        }

        return ['labels' => $labels, 'balances' => $balances];
    }

    /** Total quantity moved per movement type, for the in/out breakdown chart. */
    protected function typeBreakdown(\Illuminate\Support\Collection $movements): array
    {
        $grouped = $movements->groupBy('type')->map(fn ($rows) => (float) $rows->sum('quantity'));

        return [
            'labels' => $grouped->keys()->map(fn ($t) => str_replace('_', ' ', ucfirst($t)))->values()->all(),
            'values' => $grouped->values()->all(),
        ];
    }

    public function render(): mixed
    {
        [$product, $variant] = $this->resolveSubject();

        $warehouse = Warehouse::default();
        $stockService = app(StockService::class);
        $currentQuantity = $stockService->available($product, $variant, $warehouse);

        $batches = InventoryBatch::query()
            ->with('supplier', 'warehouse')
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->get();

        // Loaded once, unpaginated, to drive stats + both charts — this
        // item's full movement history is a modest dataset (one product's
        // worth), unlike the global Movements page which must paginate.
        $allMovements = InventoryStockMovement::query()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->orderBy('created_at')
            ->get();

        $movementsPage = InventoryStockMovement::query()
            ->with('batch', 'createdBy', 'warehouse')
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->orderByDesc('id')
            ->paginate(15);

        $totalIn = (float) $allMovements->where('quantity', '>', 0)->sum('quantity');
        $totalOut = (float) $allMovements->where('quantity', '<', 0)->sum('quantity');

        $reorderLevel = $variant ? (float) $variant->reorder_level : 0.0;

        return view('livewire.admin.inventory.stock-detail', [
            'product' => $product,
            'variant' => $variant,
            'warehouse' => $warehouse,
            'thumbnail' => $this->thumbnail($product, $variant),
            'currentQuantity' => $currentQuantity,
            'reorderLevel' => $reorderLevel,
            'batches' => $batches,
            'activeBatchCount' => $batches->where('status', 'active')->where('quantity', '>', 0)->count(),
            'totalIn' => $totalIn,
            'totalOut' => abs($totalOut),
            'lastMovement' => $allMovements->last(),
            'trend' => $this->trendData($allMovements),
            'breakdown' => $this->typeBreakdown($allMovements),
            'movements' => $movementsPage,
        ])->layout('layouts.admin.admin');
    }
}
