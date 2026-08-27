<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\File;
use App\Models\InventoryBatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Batches extends Component
{
    use WithPagination;

    public $search = '';
    public $filter = 'all';

    /** 'list' = flat per-batch-record table (current behaviour); 'grouped' = one row per batch_no. */
    public string $viewMode = 'list';

    public ?string $viewingBatchNo = null;

    protected string $paginationTheme = 'tailwind';

    protected const EXPIRING_SOON_DAYS = 30;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilter(): void { $this->resetPage(); }
    public function updatingViewMode(): void { $this->resetPage(); }

    public function viewBatch(string $batchNo): void
    {
        $this->viewingBatchNo = $batchNo;
    }

    public function closeBatchView(): void
    {
        $this->viewingBatchNo = null;
    }

    protected function buildQuery()
    {
        $expiringBefore = now()->addDays(self::EXPIRING_SOON_DAYS);

        return InventoryBatch::query()
            ->with(['product', 'variant.media', 'warehouse', 'supplier', 'purchaseOrder.supplier'])
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('batch_no', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filter === 'active', fn ($q) => $q->where('status', 'active')->where('quantity', '>', 0))
            ->when($this->filter === 'expiring', fn ($q) => $q->where('status', 'active')->where('quantity', '>', 0)
                ->whereNotNull('expiry_date')->where('expiry_date', '<=', $expiringBefore)->where('expiry_date', '>=', now()))
            ->when($this->filter === 'expired', fn ($q) => $q->whereNotNull('expiry_date')->where('expiry_date', '<', now())->where('quantity', '>', 0))
            ->when($this->filter === 'depleted', fn ($q) => $q->where('status', 'depleted'))
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date');
    }

    /**
     * One row per batch_no (a lot number can be reused across several
     * products/variants when the same physical shipment covers multiple
     * SKUs), aggregating total quantity, how many product/variant rows share
     * it, and the nearest expiry among them. Grouped in PHP rather than SQL
     * since batch_no isn't scoped to a single product and the underlying
     * dataset is the same modest table the flat list already loads in full
     * for search/filter — grouping is just a reshape of that same query.
     */
    protected function buildGroupedBatches()
    {
        $records = $this->buildQuery()->get();

        return $records->groupBy('batch_no')->map(function ($rows, $batchNo) {
            return [
                'batch_no' => $batchNo,
                'total_quantity' => $rows->sum('quantity'),
                'product_count' => $rows->pluck('product_id')->unique()->count(),
                'variant_count' => $rows->pluck('variant_id')->filter()->unique()->count(),
                'record_count' => $rows->count(),
                'nearest_expiry' => $rows->pluck('expiry_date')->filter()->sort()->first(),
                'has_expired' => $rows->contains(fn (InventoryBatch $b) => $b->expiry_date && $b->expiry_date->isPast()),
                'statuses' => $rows->pluck('status')->unique()->values(),
                'products' => $rows->pluck('product')->filter()->unique('id')->values(),
                'thumbnail_record_id' => $rows->first()->id,
            ];
        })->values();
    }

    /**
     * Resolves each batch record's thumbnail — the variant's own primary
     * image, falling back to the product's featured image — as
     * batch.id => URL, in a single batched files-table query. Calling
     * file_path()/display_image per-row here is a real N+1 on a paginated
     * table (each hits the files table individually); $batches must already
     * have 'product' and 'variant.media' eager-loaded.
     */
    protected function resolveBatchImages(Collection $batches): Collection
    {
        $fileIds = $batches->map(function (InventoryBatch $batch) {
            $variant = $batch->variant;
            $primaryMedia = $variant?->media?->firstWhere('is_primary', true) ?? $variant?->media?->first();

            return $primaryMedia?->media_id ?? $batch->product?->featured_image_id;
        })->filter()->unique()->values();

        $urlsByFileId = File::with('items')
            ->whereIn('id', $fileIds)
            ->get()
            ->mapWithKeys(function (File $file) {
                $item = $file->items->firstWhere('type', 'original');
                return [$file->id => $item ? asset('storage/' . $item->path) : null];
            })
            ->filter();

        return $batches->mapWithKeys(function (InventoryBatch $batch) use ($urlsByFileId) {
            $variant = $batch->variant;
            $primaryMedia = $variant?->media?->firstWhere('is_primary', true) ?? $variant?->media?->first();
            $fileId = $primaryMedia?->media_id ?? $batch->product?->featured_image_id;

            return [$batch->id => $fileId ? $urlsByFileId->get($fileId) : null];
        })->filter();
    }

    protected function paginateCollection($items, int $perPage = 20): LengthAwarePaginator
    {
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    public function render(): mixed
    {
        $expiringBefore = now()->addDays(self::EXPIRING_SOON_DAYS);

        $batchImages = collect();

        if ($this->viewMode === 'grouped') {
            $batches = $this->paginateCollection($this->buildGroupedBatches());

            $thumbnailRecords = InventoryBatch::with(['product', 'variant.media'])
                ->whereIn('id', collect($batches->items())->pluck('thumbnail_record_id'))
                ->get();

            $imagesByRecordId = $this->resolveBatchImages($thumbnailRecords);
            $batchImages = collect($batches->items())->mapWithKeys(
                fn (array $group) => [$group['batch_no'] => $imagesByRecordId->get($group['thumbnail_record_id'])]
            )->filter();
        } else {
            $batches = $this->buildQuery()->paginate(20);
            $batchImages = $this->resolveBatchImages(collect($batches->items()));
        }

        $viewingBatchRecords = $this->viewingBatchNo
            ? InventoryBatch::with(['product', 'variant.media', 'warehouse', 'supplier', 'purchaseOrder.supplier'])
                ->where('batch_no', $this->viewingBatchNo)
                ->orderByDesc('id')
                ->get()
            : collect();

        $viewingBatchImages = $this->resolveBatchImages($viewingBatchRecords);

        return view('livewire.admin.inventory.batches', [
            'batches' => $batches,
            'batchImages' => $batchImages,
            'viewingBatchRecords' => $viewingBatchRecords,
            'viewingBatchImages' => $viewingBatchImages,
            'expiringSoonDays' => self::EXPIRING_SOON_DAYS,
            'activeCount' => InventoryBatch::where('status', 'active')->where('quantity', '>', 0)->count(),
            'expiringCount' => InventoryBatch::where('status', 'active')->where('quantity', '>', 0)
                ->whereNotNull('expiry_date')->where('expiry_date', '<=', $expiringBefore)->where('expiry_date', '>=', now())->count(),
            'expiredCount' => InventoryBatch::whereNotNull('expiry_date')->where('expiry_date', '<', now())->where('quantity', '>', 0)->count(),
        ])->layout('layouts.admin.admin');
    }
}
