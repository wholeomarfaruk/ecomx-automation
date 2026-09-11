<?php

namespace App\Livewire\Admin\Accounts;

use App\Actions\Accounts\PostCustomerPayment;
use App\Actions\Accounts\WriteOffBadDebt;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cases 3.2-3.4 (receive payment, with allocation) and 3.10 (bad debt
 * write-off). Customers with any open/partial invoice are listed with
 * their outstanding total; "Receive Payment" auto-allocates oldest-first
 * unless the business picks specific invoices.
 */
class Receivables extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public ?int $payCustomerId = null;
    public string $payAmount = '';
    public ?int $payCashAccountId = null;
    public string $payDate = '';
    /** @var array<int, bool> */
    public array $selectedInvoices = [];

    public ?int $writeOffInvoiceId = null;

    public function openPayModal(int $customerId): void
    {
        $this->payCustomerId = $customerId;
        $this->payAmount = '';
        $this->payDate = now()->toDateString();
        $this->selectedInvoices = [];
        $this->resetValidation();
    }

    public function receivePayment(): void
    {
        $this->validate([
            'payAmount'        => 'required|numeric|gt:0',
            'payCashAccountId' => 'required|exists:accounts,id',
            'payDate'          => 'required|date',
        ]);

        $customer = Customer::findOrFail($this->payCustomerId);

        $allocations = collect($this->selectedInvoices)
            ->filter()
            ->keys()
            ->mapWithKeys(function ($invoiceId) {
                $invoice = AccountsCustomerInvoice::find($invoiceId);
                return $invoice ? [$invoiceId => $invoice->amountDue()] : [];
            })
            ->all();

        app(PostCustomerPayment::class)->handle(
            $customer,
            (int) $this->payCashAccountId,
            Account::where('code', '1100')->value('id'),
            (float) $this->payAmount,
            $this->payDate,
            $allocations,
        );

        $this->payCustomerId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded']);
    }

    public function confirmWriteOff(int $invoiceId): void
    {
        $invoice = AccountsCustomerInvoice::with('customer')->findOrFail($invoiceId);

        app(WriteOffBadDebt::class)->handle(
            $invoice->customer,
            Account::where('code', '5600')->value('id'),
            Account::where('code', '1100')->value('id'),
            $invoice->amountDue(),
            now()->toDateString(),
            $invoice,
        );

        $this->writeOffInvoiceId = null;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice written off as bad debt']);
    }

    public function render(): mixed
    {
        $customerIds = AccountsCustomerInvoice::query()
            ->whereIn('status', ['open', 'partial'])
            ->when($this->search, fn ($q) => $q->whereHas('customer', fn ($c) => $c
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
            ))
            ->distinct()
            ->pluck('customer_id');

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->paginate(15);

        foreach ($customers as $customer) {
            $customer->openInvoices = AccountsCustomerInvoice::where('customer_id', $customer->id)
                ->whereIn('status', ['open', 'partial'])
                ->oldest()
                ->get();
            $customer->totalDue = $customer->openInvoices->sum(fn ($i) => $i->amountDue());
        }

        return view('livewire.admin.accounts.receivables', [
            'customers'    => $customers,
            'cashAccounts' => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
            'payInvoices'  => $this->payCustomerId
                ? AccountsCustomerInvoice::where('customer_id', $this->payCustomerId)->whereIn('status', ['open', 'partial'])->oldest()->get()
                : collect(),
        ])->layout('layouts.admin.admin');
    }
}
