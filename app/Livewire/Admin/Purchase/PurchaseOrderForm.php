<?php

namespace App\Livewire\Admin\Purchase;

use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\File;
use App\Models\InventoryStockMovement;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\PurchasePriceHistoryService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public ?int $editingId = null;

    public string $orderNumber = '';
    public string $supplierId  = '';
    public string $orderDate   = '';
    public string $deadline    = '';
    public string $notes       = '';

    /** @var array<int, array{variant_id: string, quantity: string, unit_price: string}> */
    public array $items = [];

    /** @var array<int, string> purchase_order_item_id => quantity to receive */
    public array $receiveQuantities = [];

    public bool $showPriceHistory = false;
    public ?int $priceHistoryVariantId = null;

    public string $restockSearch = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->loadOrder($id);
            return;
        }

        $this->orderNumber = 'PO-' . str_pad((string) (PurchaseOrder::withTrashed()->max('id') + 1), 10, '0', STR_PAD_LEFT);
        $this->orderDate   = now()->format('Y-m-d');
        $this->addItem();
    }

    protected function loadOrder(int $id): void
    {
        $order = PurchaseOrder::with('items.variant.product')->findOrFail($id);

        $this->editingId  = $order->id;
        $this->orderNumber = $order->order_number;
        $this->supplierId  = (string) $order->supplier_id;
        $this->orderDate   = $order->order_date?->format('Y-m-d') ?? '';
        $this->deadline    = $order->deadline?->format('Y-m-d') ?? '';
        $this->notes       = $order->notes ?? '';

        $this->items = $order->items->map(fn ($item) => [
            'id'          => $item->id,
            'variant_id'  => (string) $item->product_variant_id,
            'quantity'    => (string) $item->quantity,
            'unit_price'  => $item->unit_price !== null ? (string) $item->unit_price : '',
        ])->all();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'variant_id' => '',
            'quantity'   => '1',
            'unit_price' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /**
     * Adds a variant from the low-stock/out-of-stock quick-add panel as a new
     * line item, pre-filled with its purchase price and its reorder quantity
     * (falling back to a sensible top-up to the reorder level, or 1) as a
     * suggested quantity — the admin can still edit it. If the variant is
     * already on the order, bumps its existing line's quantity up by the
     * suggestion instead of adding a duplicate row.
     */
    public function addLowStockItem(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return;
        }

        $suggestedQty = (float) $variant->reorder_quantity;
        if ($suggestedQty <= 0) {
            $suggestedQty = max(1, (float) $variant->reorder_level - (float) $variant->stock_quantity);
        }

        foreach ($this->items as $index => $item) {
            if ((string) $item['variant_id'] === (string) $variantId) {
                $this->items[$index]['quantity'] = (string) ((float) $item['quantity'] + $suggestedQty);
                return;
            }
        }

        // Replace a single still-empty placeholder row rather than piling up
        // blank rows every time the form is opened fresh and a + is clicked.
        if (count($this->items) === 1 && $this->items[0]['variant_id'] === '') {
            $this->items = [];
        }

        $suggestedPrice = app(PurchasePriceHistoryService::class)->suggestedPrice($variant->id);

        $this->items[] = [
            'variant_id' => (string) $variant->id,
            'quantity'   => (string) $suggestedQty,
            'unit_price' => $suggestedPrice !== null ? (string) $suggestedPrice : '',
        ];
    }

    public function updatedItems($value, $key): void
    {
        // $key looks like "0.variant_id" — only react to the variant picker changing.
        if (! str_ends_with($key, '.variant_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];

        if (! $value) {
            return;
        }

        // Switching the variant on a row always refreshes its price to that
        // variant's suggested price — an admin who wants a custom price types
        // it after picking the variant, not before.
        $suggested = app(PurchasePriceHistoryService::class)->suggestedPrice((int) $value);

        if ($suggested !== null) {
            $this->items[$index]['unit_price'] = (string) $suggested;
        }
    }

    public function viewPriceHistory(int $variantId): void
    {
        $this->priceHistoryVariantId = $variantId;
        $this->showPriceHistory = true;
    }

    public function closePriceHistory(): void
    {
        $this->showPriceHistory = false;
        $this->priceHistoryVariantId = null;
    }

    public function getGrandTotalProperty(): float
    {
        return collect($this->items)->sum(
            fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );
    }

    protected function rules(): array
    {
        return [
            'orderNumber' => 'required|string|max:190|unique:purchase_orders,order_number,' . ($this->editingId ?? 'NULL'),
            'supplierId'  => 'required|integer|exists:suppliers,id',
            'orderDate'   => 'nullable|date',
            'deadline'    => 'nullable|date',
            'items'       => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'order_number' => $this->orderNumber,
            'supplier_id'  => $this->supplierId,
            'order_date'   => $this->orderDate ?: null,
            'deadline'     => $this->deadline ?: null,
            'notes'        => $this->notes ?: null,
        ];

        if ($this->editingId) {
            $order = PurchaseOrder::findOrFail($this->editingId);
            $order->update($data);
            $message = 'Purchase order updated';
        } else {
            $data['status'] = 'pending';
            $order = PurchaseOrder::create($data);
            $message = 'Purchase order created';
        }

        $keptItemIds = [];

        foreach ($this->items as $item) {
            $quantity  = (float) $item['quantity'];
            $unitPrice = $item['unit_price'] !== '' ? (float) $item['unit_price'] : null;

            $itemData = [
                'product_variant_id' => $item['variant_id'],
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'total_amount'       => $unitPrice !== null ? round($quantity * $unitPrice, 2) : null,
            ];

            if (! empty($item['id'])) {
                $order->items()->whereKey($item['id'])->update($itemData);
                $keptItemIds[] = $item['id'];
            } else {
                $newItem = $order->items()->create($itemData);
                $keptItemIds[] = $newItem->id;
            }
        }

        // Remove any line items dropped from the form during an edit.
        $order->items()->whereNotIn('id', $keptItemIds)->delete();

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event($this->editingId ? 'updated' : 'created')
            ->log("Purchase order \"{$order->order_number}\" was " . ($this->editingId ? 'updated' : 'created'));

        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);

        $this->editingId = $order->id;
        $this->loadOrder($order->id);
    }

    public function receiveItem(int $itemId): void
    {
        $item = PurchaseOrderItem::with('variant.product', 'purchaseOrder')->findOrFail($itemId);
        $quantity = (float) ($this->receiveQuantities[$itemId] ?? 0);

        if ($quantity <= 0) {
            return;
        }

        try {
            app(StockService::class)->receivePurchaseOrderItem($item, $quantity);
        } catch (InsufficientStockException $e) {
            $this->addError("receiveQuantities.{$itemId}", $e->getMessage());
            return;
        }

        unset($this->receiveQuantities[$itemId]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Item received']);
    }

    public function render(): mixed
    {
        $variants = ProductVariant::with('product', 'values.productAttributeValue.attributeValue', 'media')->get();

        $variantOptions = $variants->mapWithKeys(function (ProductVariant $variant) {
            $labels = $variant->values->map(fn($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
            $label = trim(($variant->product->name ?? 'Unknown product') . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");

            return [$variant->id => $label];
        });

        $variantImages = $this->resolveVariantImages($variants);

        $receivingItems = collect();
        $order = null;

        if ($this->editingId) {
            $order = PurchaseOrder::findOrFail($this->editingId);
            $stockService = app(StockService::class);

            $receivingItems = PurchaseOrderItem::with('variant.product')
                ->where('purchase_order_id', $this->editingId)
                ->get();

            $batchesByItemId = $this->receivedBatchesByPurchaseOrderItem($receivingItems);

            $receivingItems = $receivingItems->map(function (PurchaseOrderItem $item) use ($stockService, $batchesByItemId) {
                $item->received_so_far = $stockService->receivedQuantityForPurchaseOrderItem($item);
                $item->remaining = max(0, (float) $item->quantity - $item->received_so_far);
                $item->received_batches = $batchesByItemId->get($item->id, collect());
                return $item;
            });
        }

        $priceHistory = collect();
        $priceHistorySummary = null;
        $priceHistoryVariant = null;

        if ($this->showPriceHistory && $this->priceHistoryVariantId) {
            $priceHistoryVariant = ProductVariant::with('product')->find($this->priceHistoryVariantId);
            $service = app(PurchasePriceHistoryService::class);
            $priceHistory = $service->forVariant($this->priceHistoryVariantId);
            $priceHistorySummary = $service->summarize($priceHistory);
        }

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $supplierOptions = $suppliers->mapWithKeys(fn (Supplier $supplier) => [$supplier->id => $supplier->name]);

        return view('livewire.admin.purchase.purchase-order-form', [
            'suppliers'       => $suppliers,
            'supplierOptions' => $supplierOptions,
            'variantOptions' => $variantOptions,
            'variantImages'  => $variantImages,
            'order'          => $order,
            'receivingItems' => $receivingItems,
            'restockGroups'  => $this->filteredRestockGroups(),
            'priceHistory'        => $priceHistory,
            'priceHistorySummary' => $priceHistorySummary,
            'priceHistoryVariant' => $priceHistoryVariant,
        ])->layout('layouts.admin.admin');
    }

    /**
     * Low-stock / out-of-stock variants, grouped by product, for the quick-add
     * panel — same "at or below its own reorder level, else the store-wide
     * threshold" rule as the Inventory Stock list's Low Stock filter. Cached
     * per-request via #[Computed]: without this, it re-queries on every
     * Livewire render, including every keystroke in the live-bound quantity/
     * price fields elsewhere on the page.
     */
    #[Computed]
    public function restockGroups()
    {
        $threshold = (int) Setting::get('low_stock_threshold', 5, 'inventory');
        $effectiveThreshold = 'COALESCE(NULLIF(reorder_level, 0), ' . $threshold . ')';

        $variants = ProductVariant::with('product')
            ->whereRaw("stock_quantity <= {$effectiveThreshold}")
            ->orderBy('stock_quantity')
            ->get();

        return $variants->groupBy('product_id')->map(function ($groupVariants) {
            return [
                'product' => $groupVariants->first()->product,
                'variants' => $groupVariants,
            ];
        })->filter(fn ($group) => $group['product'] !== null)->values();
    }

    /**
     * The distinct batches each PO item's stock was received into, as
     * purchase_order_item_id => Collection<InventoryBatch>. Batched into one
     * query across all items on the page rather than one query per item —
     * a PO item can be received across several batches (partial deliveries
     * with different lot numbers), so this can't just be a single batch_id.
     */
    protected function receivedBatchesByPurchaseOrderItem(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        return InventoryStockMovement::with('batch')
            ->where('reference_type', PurchaseOrderItem::class)
            ->whereIn('reference_id', $items->pluck('id'))
            ->whereNotNull('batch_id')
            ->get()
            ->groupBy('reference_id')
            ->map(fn (Collection $movements) => $movements->pluck('batch')->filter()->unique('id')->values());
    }

    /**
     * restockGroups() filtered by $restockSearch (product name or variant
     * SKU) — kept as a separate step so the underlying query stays cached
     * via #[Computed] regardless of what the admin types into the search box.
     */
    protected function filteredRestockGroups(): Collection
    {
        $search = trim($this->restockSearch);

        if ($search === '') {
            return $this->restockGroups;
        }

        return $this->restockGroups
            ->map(function (array $group) use ($search) {
                $productMatches = str_contains(strtolower($group['product']->name ?? ''), strtolower($search));

                $variants = $productMatches
                    ? $group['variants']
                    : $group['variants']->filter(fn (ProductVariant $variant) => str_contains(strtolower($variant->sku ?? ''), strtolower($search)));

                return ['product' => $group['product'], 'variants' => $variants->values()];
            })
            ->filter(fn (array $group) => $group['variants']->isNotEmpty())
            ->values();
    }

    /**
     * Resolves each variant's thumbnail (its own primary image, falling back
     * to the product's featured image) as variant_id => URL, in a single
     * batched query — file_path() queries the files table one row at a time,
     * so calling it per-variant here (there can be 70+ on this page) turns
     * into a genuine N+1. $variants must already have 'product' and 'media'
     * eager-loaded.
     */
    protected function resolveVariantImages(Collection $variants): Collection
    {
        $fileIds = $variants->map(function (ProductVariant $variant) {
            $primaryMedia = $variant->media->firstWhere('is_primary', true) ?? $variant->media->first();

            return $primaryMedia?->media_id ?? $variant->product?->featured_image_id;
        })->filter()->unique()->values();

        $urlsByFileId = File::with('items')
            ->whereIn('id', $fileIds)
            ->get()
            ->mapWithKeys(function (File $file) {
                $item = $file->items->firstWhere('type', 'original');
                return [$file->id => $item ? asset('storage/' . $item->path) : null];
            })
            ->filter();

        return $variants->mapWithKeys(function (ProductVariant $variant) use ($urlsByFileId) {
            $primaryMedia = $variant->media->firstWhere('is_primary', true) ?? $variant->media->first();
            $fileId = $primaryMedia?->media_id ?? $variant->product?->featured_image_id;

            return [$variant->id => $fileId ? $urlsByFileId->get($fileId) : null];
        })->filter();
    }
}
