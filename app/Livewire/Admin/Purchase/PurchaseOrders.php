<?php

namespace App\Livewire\Admin\Purchase;

use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrders extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterStatus  = '';
    public string $filterSupplier = '';

    protected string $paginationTheme = 'tailwind';

    // create/edit modal
    public bool   $formModal        = false;
    public ?int   $editingId        = null;
    public string $orderNumber      = '';
    public string $supplierId       = '';
    public string $variantId        = '';
    public string $variantSearch    = '';
    public string $variantLabel     = '';
    public string $quantity         = '';
    public string $unitPrice        = '';
    public string $orderDate        = '';
    public string $deadline         = '';
    public string $notes            = '';

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterStatus(): void   { $this->resetPage(); }
    public function updatingFilterSupplier(): void { $this->resetPage(); }

    public function getTotalAmountProperty(): float
    {
        return round((float) $this->quantity * (float) $this->unitPrice, 2);
    }

    public function selectVariant(int $id): void
    {
        $variant = ProductVariant::with('product', 'values.productAttributeValue.attributeValue')->find($id);
        if (! $variant) {
            return;
        }

        $labels = $variant->values->map(fn($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
        $this->variantId     = (string) $variant->id;
        $this->variantLabel  = trim($variant->product->name . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");
        $this->unitPrice     = $variant->purchase_price !== null ? (string) $variant->purchase_price : $this->unitPrice;
        $this->variantSearch = '';
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingId', 'orderNumber', 'supplierId', 'variantId', 'variantSearch', 'variantLabel',
            'quantity', 'unitPrice', 'orderDate', 'deadline', 'notes',
        ]);
        $this->orderNumber = 'PO-' . strtoupper(Str::random(6));
        $this->orderDate   = now()->format('Y-m-d');
        $this->resetValidation();
        $this->formModal = true;
    }

    public function editOrder(int $id): void
    {
        $order = PurchaseOrder::with('variant.product', 'variant.values.productAttributeValue.attributeValue')->findOrFail($id);

        $labels = $order->variant->values->map(fn($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');

        $this->editingId     = $order->id;
        $this->orderNumber   = $order->order_number;
        $this->supplierId    = (string) $order->supplier_id;
        $this->variantId     = (string) $order->product_variant_id;
        $this->variantLabel  = trim($order->variant->product->name . ($labels ? " ({$labels})" : '') . " [{$order->variant->sku}]");
        $this->variantSearch = '';
        $this->quantity      = (string) $order->quantity;
        $this->unitPrice     = $order->unit_price !== null ? (string) $order->unit_price : '';
        $this->orderDate     = $order->order_date?->format('Y-m-d') ?? '';
        $this->deadline      = $order->deadline?->format('Y-m-d') ?? '';
        $this->notes         = $order->notes ?? '';
        $this->resetValidation();
        $this->formModal     = true;
    }

    public function saveOrder(): void
    {
        $this->validate([
            'orderNumber' => 'required|string|max:190|unique:purchase_orders,order_number,' . ($this->editingId ?? 'NULL'),
            'supplierId'  => 'required|integer|exists:suppliers,id',
            'variantId'   => 'required|integer|exists:product_variants,id',
            'quantity'    => 'required|numeric|min:0.001',
            'unitPrice'   => 'nullable|numeric|min:0',
            'orderDate'   => 'nullable|date',
            'deadline'    => 'nullable|date',
        ]);

        $data = [
            'order_number'        => $this->orderNumber,
            'supplier_id'         => $this->supplierId,
            'product_variant_id'  => $this->variantId,
            'quantity'            => $this->quantity,
            'unit_price'          => $this->unitPrice !== '' ? $this->unitPrice : null,
            'total_amount'        => $this->unitPrice !== '' ? $this->totalAmount : null,
            'order_date'          => $this->orderDate ?: null,
            'deadline'            => $this->deadline ?: null,
            'notes'               => $this->notes ?: null,
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

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event($this->editingId ? 'updated' : 'created')
            ->log("Purchase order \"{$order->order_number}\" was " . ($this->editingId ? 'updated' : 'created'));

        $this->formModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    public function setStatus(int $id, string $status): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $order->update(['status' => $status]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "Order marked as {$status}"]);
    }

    public function deleteOrder(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $number = $order->order_number;

        $order->delete();

        activity('purchase')
            ->causedBy(auth()->user())
            ->withProperties(['order_number' => $number])
            ->event('deleted')
            ->log("Purchase order \"{$number}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Purchase order deleted']);
    }

    public function render(): mixed
    {
        $orders = PurchaseOrder::query()
            ->with('supplier', 'variant.product')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhereHas('supplier', fn($sup) => $sup->where('name', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSupplier !== '', fn($q) => $q->where('supplier_id', $this->filterSupplier))
            ->orderByDesc('id')
            ->paginate(15);

        $variantOptions = collect();
        if ($this->variantSearch !== '') {
            $variantOptions = ProductVariant::with('product')
                ->where(fn($q) => $q
                    ->whereHas('product', fn($p) => $p->where('name', 'like', "%{$this->variantSearch}%"))
                    ->orWhere('sku', 'like', "%{$this->variantSearch}%"))
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.purchase.purchase-orders', [
            'orders'          => $orders,
            'suppliers'       => Supplier::orderBy('name')->get(['id', 'name']),
            'variantOptions'  => $variantOptions,
            'totalCount'      => PurchaseOrder::count(),
            'pendingCount'    => PurchaseOrder::where('status', 'pending')->count(),
            'receivedCount'   => PurchaseOrder::where('status', 'received')->count(),
        ])->layout('layouts.admin.admin');
    }
}
