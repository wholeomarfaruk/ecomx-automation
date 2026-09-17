<?php

namespace App\Livewire\Admin\Purchase;

use App\Enums\Product\ProductType;
use App\Models\File;
use App\Models\InventoryStockMovement;
use App\Models\Product;
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

    /**
     * Each item's `variant_id` is a "picker key": "v:{id}" for a specific
     * ProductVariant (variable products) or "p:{id}" for a simple product,
     * which has no variant row of its own. Kept as one field (rather than
     * separate product_id/variant_id inputs) so the existing searchable-
     * select picker, validation, and save() logic only need to branch on
     * the prefix instead of tracking two parallel selections per row.
     *
     * @var array<int, array{variant_id: string, quantity: string, unit_price: string}>
     */
    public array $items = [];

    public bool $showPriceHistory = false;
    public ?int $priceHistoryVariantId = null;
    public ?int $priceHistoryProductId = null;

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
        $order = PurchaseOrder::with('items.product', 'items.variant')->findOrFail($id);

        $this->editingId  = $order->id;
        $this->orderNumber = $order->order_number;
        $this->supplierId  = (string) $order->supplier_id;
        $this->orderDate   = $order->order_date?->format('Y-m-d') ?? '';
        $this->deadline    = $order->deadline?->format('Y-m-d') ?? '';
        $this->notes       = $order->notes ?? '';

        $this->items = $order->items->map(fn ($item) => [
            'id'          => $item->id,
            'variant_id'  => $item->product_variant_id ? "v:{$item->product_variant_id}" : "p:{$item->product_id}",
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

        $suggestedPrice = app(PurchasePriceHistoryService::class)->suggestedPrice($variant->id);

        $this->addRestockKey("v:{$variant->id}", $suggestedQty, $suggestedPrice);
    }

    /**
     * Same as addLowStockItem(), for a simple (variant-less) product from the
     * quick-add panel — its "reorder" figures live directly on the product's
     * default-warehouse inventory_stocks row via low_stock_threshold, since
     * simple products have no reorder_level/reorder_quantity of their own.
     */
    public function addLowStockProductItem(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $threshold = (float) Setting::get('low_stock_threshold', 5, 'inventory');
        $available = app(StockService::class)->available($product, null);
        $suggestedQty = max(1, $threshold - $available);

        $suggestedPrice = app(PurchasePriceHistoryService::class)->suggestedPriceForProduct($product->id);

        $this->addRestockKey("p:{$product->id}", $suggestedQty, $suggestedPrice);
    }

    protected function addRestockKey(string $key, float $suggestedQty, ?float $suggestedPrice): void
    {
        foreach ($this->items as $index => $item) {
            if ($item['variant_id'] === $key) {
                $this->items[$index]['quantity'] = (string) ((float) $item['quantity'] + $suggestedQty);
                return;
            }
        }

        // Replace a single still-empty placeholder row rather than piling up
        // blank rows every time the form is opened fresh and a + is clicked.
        if (count($this->items) === 1 && $this->items[0]['variant_id'] === '') {
            $this->items = [];
        }

        $this->items[] = [
            'variant_id' => $key,
            'quantity'   => (string) $suggestedQty,
            'unit_price' => $suggestedPrice !== null ? (string) $suggestedPrice : '',
        ];
    }

    public function updatedItems($value, $key): void
    {
        // $key looks like "0.variant_id" — only react to the picker changing.
        if (! str_ends_with($key, '.variant_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];

        if (! $value) {
            return;
        }

        // Switching the product/variant on a row always refreshes its price
        // to that item's suggested price — an admin who wants a custom price
        // types it after picking, not before.
        $suggested = $this->suggestedPriceForKey($value);

        if ($suggested !== null) {
            $this->items[$index]['unit_price'] = (string) $suggested;
        }
    }

    protected function suggestedPriceForKey(string $key): ?float
    {
        [$type, $id] = explode(':', $key) + [null, null];

        if (! $id) {
            return null;
        }

        $service = app(PurchasePriceHistoryService::class);

        return $type === 'v' ? $service->suggestedPrice((int) $id) : $service->suggestedPriceForProduct((int) $id);
    }

    public function viewPriceHistory(string $key): void
    {
        [$type, $id] = explode(':', $key) + [null, null];

        if (! $id) {
            return;
        }

        $this->priceHistoryVariantId = $type === 'v' ? (int) $id : null;
        $this->priceHistoryProductId = $type === 'p' ? (int) $id : null;
        $this->showPriceHistory = true;
    }

    public function closePriceHistory(): void
    {
        $this->showPriceHistory = false;
        $this->priceHistoryVariantId = null;
        $this->priceHistoryProductId = null;
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
            'items.*.variant_id' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! $this->resolvePickerKey($value)) {
                    $fail('Please select a valid product or variant.');
                }
            }],
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Resolves one item row's "v:{id}"/"p:{id}" picker key into concrete
     * product_id/product_variant_id values for saving, or null if the key is
     * malformed or the referenced row no longer exists.
     *
     * @return array{product_id: int, product_variant_id: ?int}|null
     */
    protected function resolvePickerKey(string $key): ?array
    {
        [$type, $id] = explode(':', $key, 2) + [null, null];

        if (! $id || ! ctype_digit($id)) {
            return null;
        }

        if ($type === 'v') {
            $variant = ProductVariant::find((int) $id);

            return $variant ? ['product_id' => $variant->product_id, 'product_variant_id' => $variant->id] : null;
        }

        if ($type === 'p') {
            return Product::whereKey((int) $id)->exists()
                ? ['product_id' => (int) $id, 'product_variant_id' => null]
                : null;
        }

        return null;
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
            $resolved  = $this->resolvePickerKey($item['variant_id']);
            $quantity  = (float) $item['quantity'];
            $unitPrice = $item['unit_price'] !== '' ? (float) $item['unit_price'] : null;

            $itemData = [
                'product_id'         => $resolved['product_id'],
                'product_variant_id' => $resolved['product_variant_id'],
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

    public function render(): mixed
    {
        $variants = ProductVariant::with('product', 'values.productAttributeValue.attributeValue', 'media')->get();

        $variantOptions = $variants->mapWithKeys(function (ProductVariant $variant) {
            $labels = $variant->values->map(fn($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
            $label = trim(($variant->product->name ?? 'Unknown product') . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");

            return ["v:{$variant->id}" => $label];
        });

        // Simple products have no ProductVariant row, so they need their own
        // picker options ("p:{id}") alongside variants — otherwise a simple
        // product could never be ordered on a PO at all.
        $simpleProducts = Product::where('product_type', ProductType::SIMPLE->value)
            ->whereDoesntHave('variants')
            ->get(['id', 'name', 'code', 'featured_image_id']);

        $productOptions = $simpleProducts->mapWithKeys(
            fn (Product $product) => ["p:{$product->id}" => trim("{$product->name} [{$product->code}]")]
        );

        $variantOptions = $variantOptions->union($productOptions);

        $variantImages = $this->resolveVariantImages($variants)
            ->union($this->resolveProductImages($simpleProducts));

        $receivingItems = collect();
        $order = null;

        if ($this->editingId) {
            $order = PurchaseOrder::findOrFail($this->editingId);
            $stockService = app(StockService::class);

            $receivingItems = PurchaseOrderItem::with('product', 'variant')
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
        $priceHistoryProduct = null;

        if ($this->showPriceHistory) {
            $service = app(PurchasePriceHistoryService::class);

            if ($this->priceHistoryVariantId) {
                $priceHistoryVariant = ProductVariant::with('product')->find($this->priceHistoryVariantId);
                $priceHistory = $service->forVariant($this->priceHistoryVariantId);
            } elseif ($this->priceHistoryProductId) {
                $priceHistoryProduct = Product::find($this->priceHistoryProductId);
                $priceHistory = $service->forProduct($this->priceHistoryProductId);
            }

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
            'priceHistoryProduct' => $priceHistoryProduct,
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

        $variantGroups = $variants->groupBy('product_id')->map(function ($groupVariants) {
            return [
                'product' => $groupVariants->first()->product,
                'variants' => $groupVariants->map(fn (ProductVariant $variant) => (object) [
                    'key' => "v:{$variant->id}",
                    'sku' => $variant->sku,
                    'stock_quantity' => (float) $variant->stock_quantity,
                ]),
            ];
        })->filter(fn ($group) => $group['product'] !== null)->values();

        // Simple products have no variant row to carry stock_quantity/
        // reorder_level, so their own inventory_stocks balance (default
        // warehouse) is compared against the store-wide threshold instead —
        // mirrors app/Livewire/Admin/Inventory/StockList.php's simple-product rows.
        $stockService = app(StockService::class);

        $simpleProductGroups = Product::where('product_type', ProductType::SIMPLE->value)
            ->whereDoesntHave('variants')
            ->get(['id', 'name', 'code'])
            ->map(function (Product $product) use ($stockService, $threshold) {
                $available = $stockService->available($product, null);

                if ($available > $threshold) {
                    return null;
                }

                return [
                    'product' => $product,
                    'variants' => collect([(object) [
                        'key' => "p:{$product->id}",
                        'sku' => $product->code,
                        'stock_quantity' => $available,
                    ]]),
                ];
            })
            ->filter()
            ->values();

        return $variantGroups->concat($simpleProductGroups)
            ->sortBy(fn ($group) => $group['variants']->min('stock_quantity'))
            ->values();
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
                    : $group['variants']->filter(fn ($variant) => str_contains(strtolower($variant->sku ?? ''), strtolower($search)));

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

            return ["v:{$variant->id}" => $fileId ? $urlsByFileId->get($fileId) : null];
        })->filter();
    }

    /**
     * Same idea as resolveVariantImages(), for simple products (no variant
     * of their own) — just each product's own featured image, keyed by its
     * "p:{id}" picker key. $products must already carry featured_image_id.
     */
    protected function resolveProductImages(Collection $products): Collection
    {
        $fileIds = $products->pluck('featured_image_id')->filter()->unique()->values();

        $urlsByFileId = File::with('items')
            ->whereIn('id', $fileIds)
            ->get()
            ->mapWithKeys(function (File $file) {
                $item = $file->items->firstWhere('type', 'original');
                return [$file->id => $item ? asset('storage/' . $item->path) : null];
            })
            ->filter();

        return $products->mapWithKeys(function (Product $product) use ($urlsByFileId) {
            return ["p:{$product->id}" => $product->featured_image_id ? $urlsByFileId->get($product->featured_image_id) : null];
        })->filter();
    }
}
