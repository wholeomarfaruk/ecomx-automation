<?php

namespace App\Livewire\Admin\Purchase;

use App\Enums\Purchase\SupplierInvoiceType;
use App\Livewire\Traits\HandlesSupplierInvoiceModal;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Livewire\Component;
use Livewire\WithPagination;

class SupplierInvoices extends Component
{
    use WithPagination;
    use WithMediaPicker;
    use HandlesSupplierInvoiceModal;

    protected string $paginationTheme = 'tailwind';

    public string $search       = '';
    public string $filterType   = '';
    public string $filterSupplier = '';
    public string $dateFrom     = '';
    public string $dateTo       = '';

    // Which supplier the invoice modal is currently acting on — unlike
    // SupplierLedger (a fixed supplier from the route), this cross-supplier
    // page lets the admin pick one inside the modal itself.
    public string $modalSupplierId = '';

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterType(): void     { $this->resetPage(); }
    public function updatingFilterSupplier(): void { $this->resetPage(); }
    public function updatingDateFrom(): void       { $this->resetPage(); }
    public function updatingDateTo(): void         { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterType', 'filterSupplier', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    protected function invoiceSupplierId(): ?int
    {
        return $this->modalSupplierId !== '' ? (int) $this->modalSupplierId : null;
    }

    protected function extraInvoiceModalResetKeys(): array
    {
        return ['modalSupplierId'];
    }

    protected function extraInvoiceValidationRules(): array
    {
        return ['modalSupplierId' => 'required|integer|exists:suppliers,id'];
    }

    protected function afterOpenEditInvoiceModal(SupplierInvoice $invoice): void
    {
        $this->modalSupplierId = (string) $invoice->supplier_id;
    }

    public function updatedModalSupplierId(): void
    {
        $this->purchaseOrderId = '';
    }

    /**
     * Batch-load every document referenced across the page's invoices in one
     * query and attach the resolved File models to each invoice.
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
        $invoices = SupplierInvoice::query()
            ->with('supplier')
            ->withCount('items')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', "%{$this->search}%")
                ->orWhere('notes', 'like', "%{$this->search}%")
                ->orWhereHas('supplier', fn ($s) => $s
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterSupplier !== '', fn ($q) => $q->where('supplier_id', $this->filterSupplier))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('invoice_date', '<=', $this->dateTo))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20);

        $this->attachDocuments($invoices->getCollection());

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.purchase.supplier-invoices', [
            'invoices'      => $invoices,
            'suppliers'     => $suppliers,
            'totalCount'    => SupplierInvoice::count(),
            'purchaseTotal' => SupplierInvoice::where('type', SupplierInvoiceType::PURCHASE)->sum('amount'),
            'paidTotal'     => SupplierInvoice::whereIn('type', [SupplierInvoiceType::ADVANCE, SupplierInvoiceType::PAYMENT])->sum('amount'),
            ...$this->invoiceModalViewData($this->invoiceSupplierId()),
        ])->layout('layouts.admin.admin');
    }
}
