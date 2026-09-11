<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostSupplierPayment;
use App\Models\Account;
use App\Models\AccountsSupplierBill;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 4.2-4.3 — pay a supplier, optionally allocated across specific
 * bills (auto-allocates oldest-first otherwise). Mirrors Receivables.
 */
class Payables extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public ?int $paySupplierId = null;
    public string $payAmount = '';
    public ?int $payCashAccountId = null;
    public string $payDate = '';
    /** @var array<int, bool> */
    public array $selectedBills = [];

    public function openPayModal(int $supplierId): void
    {
        $this->paySupplierId = $supplierId;
        $this->payAmount = '';
        $this->payDate = now()->toDateString();
        $this->selectedBills = [];
        $this->resetValidation();
    }

    public function payNow(): void
    {
        $this->validate([
            'payAmount'        => 'required|numeric|gt:0',
            'payCashAccountId' => 'required|exists:accounts,id',
            'payDate'          => 'required|date',
        ]);

        $supplier = Supplier::findOrFail($this->paySupplierId);

        $allocations = collect($this->selectedBills)
            ->filter()
            ->keys()
            ->mapWithKeys(function ($billId) {
                $bill = AccountsSupplierBill::find($billId);
                return $bill ? [$billId => $bill->amountDue()] : [];
            })
            ->all();

        app(PostSupplierPayment::class)->handle(
            $supplier,
            Account::where('code', '2100')->value('id'),
            (int) $this->payCashAccountId,
            (float) $this->payAmount,
            $this->payDate,
            $allocations,
        );

        $this->paySupplierId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded']);
    }

    public function render(): mixed
    {
        $supplierIds = AccountsSupplierBill::query()
            ->whereIn('status', ['open', 'partial'])
            ->when($this->search, fn ($q) => $q->whereHas('supplier', fn ($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->distinct()
            ->pluck('supplier_id');

        $suppliers = Supplier::query()
            ->whereIn('id', $supplierIds)
            ->paginate(15);

        foreach ($suppliers as $supplier) {
            $supplier->openBills = AccountsSupplierBill::where('supplier_id', $supplier->id)
                ->whereIn('status', ['open', 'partial'])
                ->oldest()
                ->get();
            $supplier->totalDue = $supplier->openBills->sum(fn ($b) => $b->amountDue());
        }

        return view('livewire.admin.accounts.payables', [
            'suppliers'    => $suppliers,
            'cashAccounts' => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
            'payBills'     => $this->paySupplierId
                ? AccountsSupplierBill::where('supplier_id', $this->paySupplierId)->whereIn('status', ['open', 'partial'])->oldest()->get()
                : collect(),
        ])->layout('layouts.admin.admin');
    }
}
