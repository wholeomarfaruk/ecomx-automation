<?php

namespace App\Livewire\Admin\Inventory;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Livewire\Component;

class StockIn extends Component
{
    /** Sentinel value for the batch dropdown meaning "create a new batch" rather than an existing batch id. */
    const NEW_BATCH = '__new__';

    public $purchaseOrderId = '';
    public $purchaseOrderItemId = '';

    public $productId = '';
    public $variantId = '';
    public $quantity = '';

    public bool $useBatch = false;
    /** Either an existing InventoryBatch id, or self::NEW_BATCH to create one. */
    public string $batchSelection = self::NEW_BATCH;
    public $batchNo = '';
    public $manufactureDate = '';
    public $expiryDate = '';
    public $purchasePrice = '';

    public $note = '';

    public function updatedProductId(): void
    {
        $this->variantId = '';
        $this->resetBatchSelection();
    }

    public function updatedVariantId(): void
    {
        $this->resetBatchSelection();
    }

    protected function resetBatchSelection(): void
    {
        $this->batchSelection = self::NEW_BATCH;
        $this->batchNo = '';
        $this->manufactureDate = '';
        $this->expiryDate = '';
        $this->purchasePrice = '';
    }

    /**
     * Generates the next batch code for today (BATCH-YYYYMMDD-NNN), checked
     * against existing batch_no values so re-clicking never collides even if
     * an earlier generated code was never submitted.
     */
    public function generateBatchNo(): void
    {
        $prefix = 'BATCH-' . now()->format('Ymd') . '-';

        $usedToday = InventoryBatch::where('batch_no', 'like', $prefix . '%')
            ->pluck('batch_no')
            ->map(fn (string $no) => (int) substr($no, strlen($prefix)))
            ->max();

        $this->batchNo = $prefix . str_pad((string) (($usedToday ?? 0) + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * When the admin picks an existing batch from the dropdown, prefill the
     * (now read-only) batch_no/date/price fields from it so the summary is
     * accurate and submit() has a concrete batch_no to match against.
     */
    public function updatedBatchSelection(): void
    {
        if ($this->batchSelection === self::NEW_BATCH) {
            $this->batchNo = '';
            $this->manufactureDate = '';
            $this->expiryDate = '';
            $this->purchasePrice = '';
            return;
        }

        $batch = InventoryBatch::find($this->batchSelection);

        if (! $batch) {
            $this->batchSelection = self::NEW_BATCH;
            return;
        }

        $this->batchNo = $batch->batch_no;
        $this->manufactureDate = $batch->manufacture_date?->format('Y-m-d') ?? '';
        $this->expiryDate = $batch->expiry_date?->format('Y-m-d') ?? '';
        $this->purchasePrice = $batch->purchase_price !== null ? (string) $batch->purchase_price : '';
    }

    public function updatedPurchaseOrderId(): void
    {
        $this->purchaseOrderItemId = '';
        $this->productId = '';
        $this->variantId = '';
        $this->quantity = '';
        $this->purchasePrice = '';

        if ($this->purchaseOrderId === '') {
            $this->note = '';
            return;
        }

        $po = PurchaseOrder::find($this->purchaseOrderId);
        $this->note = $po ? "Received from PO #{$po->order_number}" : '';
    }

    public function updatedPurchaseOrderItemId(): void
    {
        if ($this->purchaseOrderItemId === '') {
            return;
        }

        $item = PurchaseOrderItem::with('variant.product')->find($this->purchaseOrderItemId);

        if (! $item) {
            return;
        }

        $remaining = max(0, (float) $item->quantity - app(StockService::class)->receivedQuantityForPurchaseOrderItem($item));

        $this->productId = (string) $item->variant->product_id;
        $this->variantId = (string) $item->product_variant_id;
        $this->quantity = $remaining > 0 ? (string) $remaining : '';
        $this->resetBatchSelection();
        $this->purchasePrice = $item->unit_price !== null ? (string) $item->unit_price : '';
    }

    public function submit(): void
    {
        $this->validate([
            'productId' => 'required|integer|exists:products,id',
            'variantId' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|numeric|min:0.001',
            'batchNo' => ($this->useBatch && $this->batchSelection === self::NEW_BATCH) ? 'required|string|max:100' : 'nullable|string|max:100',
            'manufactureDate' => 'nullable|date',
            'expiryDate' => 'nullable|date|after_or_equal:manufactureDate',
            'purchasePrice' => 'nullable|numeric|min:0',
        ]);

        if ($this->useBatch && $this->batchSelection !== self::NEW_BATCH && ! InventoryBatch::whereKey($this->batchSelection)->exists()) {
            $this->addError('batchSelection', 'Please choose a batch or create a new one.');
            return;
        }

        $product = Product::findOrFail($this->productId);
        $variant = $this->variantId ? ProductVariant::findOrFail($this->variantId) : null;
        $purchaseOrder = $this->purchaseOrderId ? PurchaseOrder::find($this->purchaseOrderId) : null;
        $purchaseOrderItem = $this->purchaseOrderItemId ? PurchaseOrderItem::find($this->purchaseOrderItemId) : null;

        $stockService = app(StockService::class);

        if ($purchaseOrderItem) {
            $remaining = max(0, (float) $purchaseOrderItem->quantity - $stockService->receivedQuantityForPurchaseOrderItem($purchaseOrderItem));

            if ((float) $this->quantity > $remaining) {
                $this->addError('quantity', "This exceeds what's left on this item ({$remaining} remaining of {$purchaseOrderItem->quantity} ordered).");
                return;
            }
        }

        try {
            if ($this->useBatch) {
                $stockService->stockInBatch(
                    $product,
                    $variant,
                    $this->batchNo,
                    (float) $this->quantity,
                    expiryDate: $this->expiryDate ?: null,
                    manufactureDate: $this->manufactureDate ?: null,
                    purchasePrice: $this->purchasePrice !== '' ? (float) $this->purchasePrice : null,
                    reference: $purchaseOrderItem,
                    note: $this->note ?: 'Stock in from Inventory',
                    purchaseOrder: $purchaseOrder,
                );

                if ($purchaseOrderItem) {
                    // The movement above is now logged against the PO item
                    // (reference_type = PurchaseOrderItem), so received-so-far
                    // tracking sees it — check if the whole PO is now complete.
                    $stockService->markPurchaseOrderReceivedIfComplete($purchaseOrder);
                }
            } elseif ($purchaseOrderItem) {
                $stockService->receivePurchaseOrderItem($purchaseOrderItem, (float) $this->quantity, note: $this->note ?: null);
            } else {
                $stockService->increase(
                    $product,
                    $variant,
                    (float) $this->quantity,
                    'purchase',
                    note: $this->note ?: 'Stock in from Inventory',
                );
            }
        } catch (InsufficientStockException $e) {
            $this->addError('quantity', $e->getMessage());
            return;
        }

        $toastMessage = 'Stock added';

        if ($purchaseOrderItem) {
            $totalReceived = $stockService->receivedQuantityForPurchaseOrderItem($purchaseOrderItem->fresh());
            $toastMessage = "Stock added — {$totalReceived} of {$purchaseOrderItem->quantity} received for this item so far";
        }

        $this->reset([
            'purchaseOrderId', 'purchaseOrderItemId', 'variantId', 'quantity',
            'useBatch', 'batchSelection', 'batchNo', 'manufactureDate', 'expiryDate', 'purchasePrice', 'note',
        ]);
        $this->batchSelection = self::NEW_BATCH;
        $this->dispatch('toast', ['type' => 'success', 'message' => $toastMessage]);
    }

    public function render(): mixed
    {
        $variants = $this->productId
            ? ProductVariant::where('product_id', $this->productId)->orderBy('sort_order')->get(['id', 'sku', 'stock_quantity'])
            : collect();

        $existingBatches = collect();
        if ($this->useBatch && $this->productId) {
            $existingBatches = InventoryBatch::query()
                ->where('warehouse_id', Warehouse::default()->id)
                ->where('product_id', $this->productId)
                ->where('variant_id', $this->variantId ?: null)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->get();
        }

        $stockService = app(StockService::class);

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        $purchaseOrderItems = collect();
        if ($this->purchaseOrderId !== '') {
            $purchaseOrderItems = PurchaseOrderItem::with('variant.product')
                ->where('purchase_order_id', $this->purchaseOrderId)
                ->get()
                ->map(function (PurchaseOrderItem $item) use ($stockService) {
                    $item->received_so_far = $stockService->receivedQuantityForPurchaseOrderItem($item);
                    $item->remaining = max(0, (float) $item->quantity - $item->received_so_far);
                    return $item;
                });
        }

        return view('livewire.admin.inventory.stock-in', [
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'variants' => $variants,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrderItems' => $purchaseOrderItems,
            'existingBatches' => $existingBatches,
        ])->layout('layouts.admin.admin');
    }
}
