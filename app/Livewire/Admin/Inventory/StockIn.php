<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Inventory\StockReceivingService;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Stock In = goods receiving. One delivery (optionally against a purchase
 * order) is received in one go: many products / variants, each line with
 * its own batch. Saving creates one receiving (GRN) and one batch per line.
 */
class StockIn extends Component
{
    public $purchaseOrderId = '';
    public $supplierId = '';
    public $warehouseId = '';
    public $receivedAt = '';
    public $reference = '';
    public $note = '';

    /** @var list<array{uid: string, product_id: string, variant_id: string, purchase_order_item_id: ?int, quantity: string, unit_cost: string, batch_id: string, batch_no: string, manufacture_date: string, expiry_date: string}> */
    public array $lines = [];

    /** Product picked in "Add product" — adds one line per variant. */
    public $addProductId = '';

    /** SKU / barcode scan box. */
    public string $scan = '';

    public function mount(): void
    {
        $this->warehouseId = (string) Warehouse::default()->id;
        $this->receivedAt = now()->format('Y-m-d H:i:s');

        // Deep link from a PO: ?purchase_order=X (all remaining lines) or &item=Y (just that line).
        if ($poId = request()->query('purchase_order')) {
            $this->purchaseOrderId = (string) $poId;
            $this->loadPurchaseOrder(request()->query('item') ? (int) request()->query('item') : null);
        }
    }

    protected function blankLine(array $over = []): array
    {
        return array_merge([
            'uid' => (string) Str::uuid(), 'product_id' => '', 'variant_id' => '', 'purchase_order_item_id' => null,
            'quantity' => '', 'unit_cost' => '', 'batch_id' => '', 'batch_no' => '', 'manufacture_date' => '', 'expiry_date' => '',
        ], $over);
    }

    protected function defaultCost(?int $productId, ?int $variantId): string
    {
        $c = $variantId ? ProductVariant::whereKey($variantId)->value('purchase_price') : null;
        $c ??= $productId ? Product::whereKey($productId)->value('purchase_price') : null;

        return $c !== null ? (string) (float) $c : '';
    }

    /* ---------------- purchase order ---------------- */

    public function updatedPurchaseOrderId(): void
    {
        $this->loadPurchaseOrder();
    }

    /** Replace PO lines with every line that still has quantity left (or just one line). */
    public function loadPurchaseOrder(?int $onlyItem = null): void
    {
        // Drop lines that came from a PO; keep anything typed by hand.
        $this->lines = array_values(array_filter($this->lines, fn ($l) => empty($l['purchase_order_item_id'])));

        $po = $this->purchaseOrderId !== '' ? PurchaseOrder::with('items')->find($this->purchaseOrderId) : null;
        if (! $po) {
            $this->purchaseOrderId = '';

            return;
        }

        $this->supplierId = (string) ($po->supplier_id ?? '');
        $stock = app(StockService::class);
        foreach ($po->items as $item) {
            if ($onlyItem && (int) $item->id !== $onlyItem) {
                continue;
            }
            $left = max(0, (float) $item->quantity - $stock->receivedQuantityForPurchaseOrderItem($item));
            if ($left <= 0) {
                continue;
            }
            $this->lines[] = $this->blankLine([
                'product_id' => (string) $item->product_id, 'variant_id' => $item->product_variant_id ? (string) $item->product_variant_id : '',
                'purchase_order_item_id' => $item->id, 'quantity' => (string) $left,
                'unit_cost' => $item->unit_price !== null ? (string) (float) $item->unit_price : $this->defaultCost($item->product_id, $item->product_variant_id),
            ]);
        }

        if (! $this->lines) {
            $this->dispatch('toast', ['type' => 'info', 'message' => "PO #{$po->order_number} is already fully received."]);
        }
    }

    /* ---------------- lines ---------------- */

    /** "Add product": one line per active variant (or one line for a simple product). */
    public function updatedAddProductId(): void
    {
        $pid = (int) $this->addProductId;
        if (! $pid || ! Product::whereKey($pid)->exists()) {
            return;
        }

        $variants = ProductVariant::where('product_id', $pid)->where('status', 'active')->orderBy('sort_order')->get(['id', 'purchase_price']);
        $have = collect($this->lines)->filter(fn ($l) => (int) $l['product_id'] === $pid && $l['batch_id'] === '' && empty($l['purchase_order_item_id']))->pluck('variant_id')->map(fn ($v) => (string) $v)->all();

        if ($variants->isEmpty()) {
            if (! in_array('', $have, true)) {
                $this->lines[] = $this->blankLine(['product_id' => (string) $pid, 'unit_cost' => $this->defaultCost($pid, null)]);
            }
        } else {
            $productCost = Product::whereKey($pid)->value('purchase_price');
            foreach ($variants as $v) {
                if (in_array((string) $v->id, $have, true)) {
                    continue;
                }
                $c = $v->purchase_price ?? $productCost;
                $this->lines[] = $this->blankLine(['product_id' => (string) $pid, 'variant_id' => (string) $v->id, 'unit_cost' => $c !== null ? (string) (float) $c : '']);
            }
        }

        $this->addProductId = '';
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
        $this->resetErrorBag();
    }

    public function duplicateLine(int $i): void
    {
        if (isset($this->lines[$i])) {
            // Same item into a second (new) batch, e.g. a different expiry.
            array_splice($this->lines, $i + 1, 0, [array_merge($this->lines[$i], ['uid' => (string) Str::uuid(), 'quantity' => '', 'batch_id' => '', 'batch_no' => '', 'purchase_order_item_id' => null])]);
        }
    }

    /** Copy this line's cost / dates to every other line of the same product. */
    public function applyToProduct(int $i, string $field): void
    {
        if (! isset($this->lines[$i]) || ! in_array($field, ['unit_cost', 'manufacture_date', 'expiry_date'], true)) {
            return;
        }
        $src = $this->lines[$i];
        foreach ($this->lines as $k => $l) {
            if ($l['product_id'] === $src['product_id'] && $l['batch_id'] === '') {
                $this->lines[$k][$field] = $src[$field];
            }
        }
    }

    public function updatedLines($value, string $key): void
    {
        [$i, $field] = array_pad(explode('.', $key, 2), 2, null);
        if (! isset($this->lines[$i])) {
            return;
        }

        if ($field === 'product_id') {
            $this->lines[$i]['variant_id'] = '';
            $this->lines[$i]['batch_id'] = '';
            $this->lines[$i]['purchase_order_item_id'] = null;
            $this->lines[$i]['unit_cost'] = $this->defaultCost((int) $value ?: null, null);
        }
        if ($field === 'variant_id') {
            $this->lines[$i]['batch_id'] = '';
            $this->lines[$i]['unit_cost'] = $this->defaultCost((int) $this->lines[$i]['product_id'] ?: null, (int) $value ?: null);
        }
        if ($field === 'batch_id' && $value !== '' && ($b = InventoryBatch::find($value))) {
            // Topping up an existing batch: it keeps its own number and dates.
            $this->lines[$i]['batch_no'] = '';
            $this->lines[$i]['manufacture_date'] = $b->manufacture_date?->format('Y-m-d') ?? '';
            $this->lines[$i]['expiry_date'] = $b->expiry_date?->format('Y-m-d') ?? '';
        }
    }

    /** Scan / type a variant SKU: adds the variant, or +1 on its open line. */
    public function scanSku(): void
    {
        $sku = trim($this->scan);
        $this->scan = '';
        if ($sku === '') {
            return;
        }

        $v = ProductVariant::where('sku', $sku)->first(['id', 'product_id', 'purchase_price']);
        if (! $v) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "No variant with SKU {$sku}."]);

            return;
        }

        foreach ($this->lines as $k => $l) {
            if ((int) $l['variant_id'] === $v->id && $l['batch_id'] === '') {
                $this->lines[$k]['quantity'] = (string) ((float) $l['quantity'] + 1);

                return;
            }
        }
        $this->lines[] = $this->blankLine(['product_id' => (string) $v->product_id, 'variant_id' => (string) $v->id, 'quantity' => '1',
            'unit_cost' => $this->defaultCost($v->product_id, $v->id)]);
    }

    /* ---------------- save ---------------- */

    public function save(): void
    {
        $this->validate([
            'warehouseId' => 'required|integer|exists:warehouses,id',
            'supplierId' => 'nullable|integer|exists:suppliers,id',
            'purchaseOrderId' => 'nullable|integer|exists:purchase_orders,id',
            'receivedAt' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.variant_id' => 'nullable|integer',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.batch_no' => 'nullable|string|max:100',
            'lines.*.manufacture_date' => 'nullable|date',
            'lines.*.expiry_date' => 'nullable|date',
        ], [
            'lines.required' => 'Add at least one product.',
            'lines.*.product_id.required' => 'Choose a product.',
            'lines.*.quantity.required' => 'Quantity?',
            'lines.*.quantity.min' => 'Must be more than 0.',
        ]);

        $grn = app(StockReceivingService::class)->receive([
            'warehouse_id' => (int) $this->warehouseId, 'supplier_id' => $this->supplierId !== '' ? (int) $this->supplierId : null,
            'purchase_order_id' => $this->purchaseOrderId !== '' ? (int) $this->purchaseOrderId : null,
            'received_at' => $this->receivedAt, 'reference' => trim((string) $this->reference), 'note' => trim((string) $this->note),
        ], array_map(fn ($l) => [
            'product_id' => (int) $l['product_id'], 'variant_id' => $l['variant_id'] !== '' ? (int) $l['variant_id'] : null,
            'purchase_order_item_id' => $l['purchase_order_item_id'] ?: null, 'quantity' => (float) $l['quantity'],
            'unit_cost' => $l['unit_cost'] !== '' ? (float) $l['unit_cost'] : null, 'batch_id' => $l['batch_id'] !== '' ? (int) $l['batch_id'] : null,
            'batch_no' => $l['batch_no'], 'manufacture_date' => $l['manufacture_date'], 'expiry_date' => $l['expiry_date'],
        ], $this->lines));

        session()->flash('toast', ['type' => 'success', 'message' => "{$grn->grn_number} saved — {$grn->line_count} lines, " . rtrim(rtrim(number_format((float) $grn->total_quantity, 3), '0'), '.') . ' pcs received.']);
        $this->redirectRoute('admin.inventory.receivings.show', $grn, navigate: true);
    }

    /* ---------------- render ---------------- */

    /** "SKU · Black / M" for each variant of the products on the lines (one query). */
    protected function variantOptions(array $productIds): Collection
    {
        if (! $productIds) {
            return collect();
        }

        return ProductVariant::with('values.productAttributeValue.attributeValue.attribute')
            ->whereIn('product_id', $productIds)->orderBy('sort_order')
            ->get(['id', 'product_id', 'sku', 'stock_quantity', 'status'])
            ->groupBy('product_id')
            ->map(fn ($vs) => $vs->mapWithKeys(fn ($v) => [(string) $v->id => trim(($v->sku ?: '#' . $v->id) . ($v->options_map ? ' · ' . implode(' / ', $v->options_map) : '')
                . ' — stock ' . rtrim(rtrim(number_format((float) $v->stock_quantity, 3), '0'), '.') . ($v->status !== 'active' ? ' (inactive)' : ''))]));
    }

    public function render(): mixed
    {
        $productIds = collect($this->lines)->pluck('product_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
        $variantOptions = $this->variantOptions($productIds);

        // Existing batches, item-wise: only batches of the line's own product + variant at this warehouse.
        $batches = $productIds ? InventoryBatch::where('warehouse_id', (int) $this->warehouseId)->whereIn('product_id', $productIds)
            ->where('status', 'active')->orderByDesc('id')->get(['id', 'product_id', 'variant_id', 'batch_no', 'quantity', 'expiry_date'])
            ->groupBy(fn ($b) => $b->product_id . '|' . ($b->variant_id ?? '')) : collect();

        $poItems = collect();
        if ($this->purchaseOrderId !== '') {
            $stock = app(StockService::class);
            $poItems = PurchaseOrderItem::where('purchase_order_id', $this->purchaseOrderId)->get()
                ->mapWithKeys(fn ($it) => [$it->id => ['ordered' => (float) $it->quantity, 'received' => $stock->receivedQuantityForPurchaseOrderItem($it)]]);
        }

        $qty = collect($this->lines)->sum(fn ($l) => (float) $l['quantity']);
        $cost = collect($this->lines)->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_cost']);

        return view('livewire.admin.inventory.stock-in', [
            'products' => Product::orderBy('name')->pluck('name', 'id'),
            'productNames' => Product::whereIn('id', $productIds)->pluck('name', 'id'),
            'variantOptions' => $variantOptions,
            'batches' => $batches,
            'poItems' => $poItems,
            'purchaseOrders' => PurchaseOrder::with('supplier:id,name')->where('status', 'pending')->orderByDesc('id')->get(['id', 'order_number', 'supplier_id']),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'warehouses' => Warehouse::where('status', 'active')->orderByDesc('is_default')->orderBy('name')->pluck('name', 'id'),
            'totals' => ['lines' => count($this->lines), 'qty' => $qty, 'cost' => $cost],
        ])->layout('layouts.admin.admin');
    }
}
