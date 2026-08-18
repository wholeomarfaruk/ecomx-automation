<?php

namespace App\Livewire\Admin\Purchase;

use App\Enums\Purchase\SupplierInvoiceType;
use App\Exceptions\Purchase\SupplierInvoiceDeletionException;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierLedger extends Component
{
    use WithPagination;
    use WithMediaPicker;

    public int $supplierId;

    protected string $paginationTheme = 'tailwind';

    // create/edit invoice modal
    public bool    $invoiceModal      = false;
    public ?int    $editingInvoiceId  = null;
    public bool    $editingIsLocked   = false;
    public string  $invoiceType       = 'purchase';
    public string  $invoiceNumber     = '';
    public string  $invoiceDate       = '';
    public bool    $invoiceIsAdjusted = false;
    public string  $invoiceNotes      = '';

    /** @var array<int, int> File IDs attached to the invoice being created/edited. */
    public array $documentIds = [];

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
        $this->reset(['editingInvoiceId', 'editingIsLocked', 'invoiceType', 'invoiceNumber', 'invoiceIsAdjusted', 'invoiceNotes', 'items', 'variantSearch', 'manualAmount', 'documentIds']);
        $this->invoiceType = 'purchase';
        $this->invoiceDate = now()->format('Y-m-d');
        $this->resetValidation();
        $this->invoiceModal = true;
    }

    public function openEditInvoiceModal(int $id): void
    {
        $invoice = SupplierInvoice::with('items')->findOrFail($id);

        $this->reset(['items', 'variantSearch', 'manualAmount']);

        $this->editingInvoiceId  = $invoice->id;
        $this->editingIsLocked   = ! $invoice->isLatestSerial();
        $this->invoiceType       = $invoice->type->value;
        $this->invoiceNumber     = $invoice->invoice_number ?? '';
        $this->invoiceDate       = $invoice->invoice_date?->format('Y-m-d') ?? '';
        $this->invoiceIsAdjusted = $invoice->is_adjusted;
        $this->invoiceNotes      = $invoice->notes ?? '';
        $this->manualAmount      = (string) $invoice->amount;
        $this->documentIds       = $invoice->document_ids ?? [];
        $this->items             = $invoice->items->map(fn ($item) => [
            'variant_id' => $item->product_variant_id ? (string) $item->product_variant_id : '',
            'name'       => $item->name,
            'quantity'   => $item->quantity !== null ? (string) $item->quantity : '',
            'unit_price' => $item->unit_price !== null ? (string) $item->unit_price : '',
            'amount'     => (string) $item->amount,
        ])->all();

        $this->resetValidation();
        $this->invoiceModal = true;
    }

    public function updatedInvoiceType(): void
    {
        if (! in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value])) {
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
        if ($this->editingInvoiceId) {
            $this->updateInvoice();
            return;
        }

        $this->createInvoice();
    }

    /**
     * Only the latest invoice may have its type/amount/items changed —
     * every older invoice is locked to metadata-only edits (invoice
     * number, date, adjusted flag, notes, documents), since changing an
     * older invoice's amount would silently corrupt every running balance
     * and delta computed for invoices recorded after it.
     */
    protected function updateInvoice(): void
    {
        $invoice = SupplierInvoice::findOrFail($this->editingInvoiceId);

        if ($invoice->isLatestSerial()) {
            $this->updateLatestInvoice($invoice);
            return;
        }

        $this->updateInvoiceMetadata($invoice);
    }

    protected function updateInvoiceMetadata(SupplierInvoice $invoice): void
    {
        $this->validate([
            'invoiceNumber' => 'nullable|string|max:100',
            'invoiceDate'   => 'nullable|date',
            'invoiceNotes'  => 'nullable|string',
        ]);

        $invoice->update([
            'invoice_number' => $this->invoiceNumber ?: null,
            'is_adjusted'    => $this->invoiceIsAdjusted,
            'invoice_date'   => $this->invoiceDate ?: null,
            'notes'          => $this->invoiceNotes ?: null,
            'document_ids'   => $this->documentIds ?: null,
        ]);

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->event('updated')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) updated for \"{$invoice->supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice updated successfully']);
    }

    protected function updateLatestInvoice(SupplierInvoice $invoice): void
    {
        $usesItems = in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value]);

        $rules = [
            'invoiceType'   => ['required', Rule::enum(SupplierInvoiceType::class)],
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

        $invoice->update([
            'invoice_number' => $this->invoiceNumber ?: null,
            'type'           => $this->invoiceType,
            'amount'         => $amount,
            'is_adjusted'    => $this->invoiceIsAdjusted,
            'invoice_date'   => $this->invoiceDate ?: null,
            'notes'          => $this->invoiceNotes ?: null,
            'document_ids'   => $this->documentIds ?: null,
        ]);

        $invoice->items()->delete();

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
            ->withProperties(['type' => $invoice->type->value, 'amount' => $invoice->amount])
            ->event('updated')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) updated for \"{$invoice->supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice updated successfully']);
    }

    protected function createInvoice(): void
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $usesItems = in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value]);

        $rules = [
            'invoiceType'   => ['required', Rule::enum(SupplierInvoiceType::class)],
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
            'document_ids'    => $this->documentIds ?: null,
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
            ->withProperties(['type' => $invoice->type->value, 'amount' => $invoice->amount])
            ->event('created')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) recorded for \"{$supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice recorded successfully']);
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = SupplierInvoice::findOrFail($id);

        try {
            $invoice->delete();
        } catch (SupplierInvoiceDeletionException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice deleted']);
    }

    /**
     * Set a transient `balance_after` on each invoice: the supplier's due (+)
     * or advance (-) balance immediately after that invoice was recorded.
     *
     * Invoices are walked in the newest-first order they're displayed in.
     * The walk starts at the current Supplier balance, offset by the signed
     * amount of every invoice newer than the first one on this page (so the
     * figures are correct on page 2+, not just page 1).
     */
    protected function attachRunningBalances(Supplier $supplier, $invoices): void
    {
        if ($invoices->isEmpty()) {
            return;
        }

        $firstSerialOnPage = $invoices->first()->serial_number;

        $newerInvoices = $supplier->invoices()
            ->where('serial_number', '>', $firstSerialOnPage)
            ->get(['type', 'amount']);

        $newerDelta = $newerInvoices->sum(fn (SupplierInvoice $invoice) => $invoice->type->signedAmount((float) $invoice->amount));

        $runningBalance = (float) $supplier->balance - $newerDelta;
        $isFirstRow     = $newerInvoices->isEmpty();

        foreach ($invoices as $invoice) {
            $invoice->balance_after   = $runningBalance;
            $invoice->is_latest_serial = $isFirstRow;

            $runningBalance -= $invoice->type->signedAmount((float) $invoice->amount);
            $isFirstRow = false;
        }
    }

    /**
     * Batch-load every document referenced across the page's invoices in one
     * query and attach the resolved File models to each invoice, avoiding a
     * File::find() per document per row in the view.
     */
    protected function attachDocuments($invoices): void
    {
        $documentIds = $invoices->flatMap(fn (SupplierInvoice $invoice) => $invoice->document_ids ?? [])->unique();

        if ($documentIds->isEmpty()) {
            return;
        }

        $files = File::whereIn('id', $documentIds)->get()->keyBy('id');

        foreach ($invoices as $invoice) {
            $invoice->documents = collect($invoice->document_ids ?? [])
                ->map(fn ($id) => $files->get($id))
                ->filter()
                ->values();
        }
    }

    public function render(): mixed
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $invoices = $supplier->invoices()
            ->withCount('items')
            ->orderByDesc('serial_number')
            ->paginate(15);

        $this->attachRunningBalances($supplier, $invoices->getCollection());
        $this->attachDocuments($invoices->getCollection());

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
            'purchaseTotal'  => $supplier->invoices()->where('type', SupplierInvoiceType::PURCHASE)->sum('amount'),
            'paidTotal'      => $supplier->invoices()->whereIn('type', [SupplierInvoiceType::ADVANCE, SupplierInvoiceType::PAYMENT])->sum('amount'),
        ])->layout('layouts.admin.admin');
    }
}
