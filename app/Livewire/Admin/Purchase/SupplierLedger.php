<?php

namespace App\Livewire\Admin\Purchase;

use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierLedger extends Component
{
    use WithPagination;

    public int $supplierId;

    protected string $paginationTheme = 'tailwind';

    // create invoice modal
    public bool   $invoiceModal      = false;
    public string $invoiceType       = 'purchase';
    public string $invoiceNumber     = '';
    public string $invoiceDate       = '';
    public bool   $invoiceIsAdjusted = false;
    public string $invoiceNotes      = '';

    /** @var array<int, array{variant_id: string, name: string, quantity: string, unit_price: string, amount: string}> */
    public array $items = [];

    public string $variantSearch = '';
    public string $manualAmount  = '';

    public function mount(int $supplierId): void
    {
        $this->supplierId  = $supplierId;
        $this->invoiceDate = now()->format('Y-m-d');
    }

    public function openInvoiceModal(): void
    {
        $this->reset(['invoiceType', 'invoiceNumber', 'invoiceIsAdjusted', 'invoiceNotes', 'items', 'variantSearch', 'manualAmount']);
        $this->invoiceType = 'purchase';
        $this->invoiceDate = now()->format('Y-m-d');
        $this->resetValidation();
        $this->invoiceModal = true;
    }

    public function updatedInvoiceType(): void
    {
        if (! in_array($this->invoiceType, ['purchase', 'return'])) {
            $this->items = [];
        }
    }

    public function addItem(?int $variantId = null): void
    {
        $name = '';
        $unitPrice = '';

        if ($variantId) {
            $variant = ProductVariant::with('values.productAttributeValue.attributeValue', 'product')->find($variantId);
            if ($variant) {
                $labels = $variant->values->map(fn($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
                $name = trim($variant->product->name . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");
                $unitPrice = $variant->purchase_price !== null ? (string) $variant->purchase_price : '';
            }
        }

        $this->items[] = [
            'variant_id' => $variantId ? (string) $variantId : '',
            'name'       => $name,
            'quantity'   => '1',
            'unit_price' => $unitPrice,
            'amount'     => '',
        ];

        $this->variantSearch = '';
        $this->recalculateItemAmount(count($this->items) - 1);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if (in_array($field, ['quantity', 'unit_price']) && $index !== null) {
            $this->recalculateItemAmount((int) $index);
        }
    }

    protected function recalculateItemAmount(int $index): void
    {
        $qty   = (float) ($this->items[$index]['quantity'] ?? 0);
        $price = (float) ($this->items[$index]['unit_price'] ?? 0);

        if ($qty > 0 && $price > 0) {
            $this->items[$index]['amount'] = (string) round($qty * $price, 2);
        }
    }

    public function getItemsTotalProperty(): float
    {
        return collect($this->items)->sum(fn($item) => (float) ($item['amount'] ?? 0));
    }

    public function saveInvoice(): void
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $usesItems = in_array($this->invoiceType, ['purchase', 'return']);

        $rules = [
            'invoiceType'   => 'required|in:purchase,advance,payment,return',
            'invoiceNumber' => 'nullable|string|max:100',
            'invoiceDate'   => 'nullable|date',
            'invoiceNotes'  => 'nullable|string',
        ];

        if ($usesItems) {
            $rules['items']               = 'required|array|min:1';
            $rules['items.*.name']        = 'required|string|max:255';
            $rules['items.*.quantity']    = 'nullable|numeric|min:0';
            $rules['items.*.unit_price']  = 'nullable|numeric|min:0';
            $rules['items.*.amount']      = 'required|numeric|min:0.01';
        } else {
            $rules['manualAmount'] = 'required|numeric|min:0.01';
        }

        $this->validate($rules);

        $amount = $usesItems ? $this->itemsTotal : (float) $this->manualAmount;

        if ($amount <= 0) {
            $this->addError('items', 'Total amount must be greater than zero.');
            return;
        }

        $serial = ($supplier->invoices()->max('serial_number') ?? 0) + 1;

        $invoice = SupplierInvoice::create([
            'supplier_id'     => $supplier->id,
            'serial_number'   => $serial,
            'invoice_number'  => $this->invoiceNumber ?: null,
            'type'            => $this->invoiceType,
            'amount'          => $amount,
            'is_adjusted'     => $this->invoiceIsAdjusted,
            'invoice_date'    => $this->invoiceDate ?: null,
            'notes'           => $this->invoiceNotes ?: null,
        ]);

        if ($usesItems) {
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'product_variant_id' => $item['variant_id'] ?: null,
                    'name'                => $item['name'],
                    'quantity'            => $item['quantity'] !== '' ? $item['quantity'] : null,
                    'unit_price'          => $item['unit_price'] !== '' ? $item['unit_price'] : null,
                    'amount'              => $item['amount'],
                ]);
            }
        }

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->withProperties(['type' => $invoice->type, 'amount' => $invoice->amount])
            ->event('created')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type}) recorded for \"{$supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice recorded successfully']);
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = SupplierInvoice::findOrFail($id);

        if ($invoice->purchaseOrders()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete an invoice linked to a purchase order']);
            return;
        }

        $invoice->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice deleted']);
    }

    public function render(): mixed
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $invoices = $supplier->invoices()
            ->withCount('items')
            ->orderByDesc('serial_number')
            ->paginate(15);

        $variantOptions = collect();
        if ($this->variantSearch !== '') {
            $variantOptions = ProductVariant::with('product', 'values.productAttributeValue.attributeValue')
                ->where(fn($q) => $q
                    ->whereHas('product', fn($p) => $p->where('name', 'like', "%{$this->variantSearch}%"))
                    ->orWhere('sku', 'like', "%{$this->variantSearch}%"))
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.purchase.supplier-ledger', [
            'supplier'       => $supplier,
            'invoices'       => $invoices,
            'variantOptions' => $variantOptions,
            'purchaseTotal'  => $supplier->invoices()->where('type', 'purchase')->sum('amount'),
            'paidTotal'      => $supplier->invoices()->whereIn('type', ['advance', 'payment'])->sum('amount'),
        ])->layout('layouts.admin.admin');
    }
}
