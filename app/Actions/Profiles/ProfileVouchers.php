<?php

namespace App\Actions\Profiles;

use App\Actions\Accounts\PostCustomerPayment;
use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\PostOwnerInvestment;
use App\Actions\Accounts\PostOwnerWithdrawal;
use App\Actions\Accounts\PostSupplierAdvance;
use App\Actions\Accounts\PostSupplierPayment;
use App\Enums\Accounts\TransactionType;
use App\Enums\Purchase\SupplierInvoiceType;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\AccountsSupplierBill;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use App\Support\Bdt;
use Illuminate\Support\Facades\DB;

/**
 * The money buttons on the profile pages, each a thin wrapper over the
 * existing Accounts posting actions so every voucher still goes through
 * PostJournalEntry (balanced, period lock, duplicate guard), plus a line
 * on the profile's activity timeline.
 */
class ProfileVouchers
{
    public function __construct(
        protected LedgerAccounts $accounts,
        protected PartyRecords $parties,
        protected InstallmentPlans $plans,
        protected PostJournalEntry $postJournalEntry,
    ) {}

    /** Receipt voucher: Dr cash / Cr receivable. Applied to an active kisti plan if there is one. */
    public function collect(MasterProfile $profile, float $amount, int $cashAccountId, string $date, ?string $note = null): JournalEntry
    {
        return DB::transaction(function () use ($profile, $amount, $cashAccountId, $date, $note) {
            $customer = $this->parties->customerFor($profile);
            $profile->addRole('customer');

            $entry = app(PostCustomerPayment::class)->handle(
                $customer, $cashAccountId, $this->accounts->require(LedgerAccounts::RECEIVABLE), $amount, $date,
                description: $note ?: "Payment received from {$profile->display_name}",
            );

            $this->plans->applyPayment($profile, 'customer', $amount, $entry->id);
            $profile->logActivity('receipt', 'Payment received ' . Bdt::money($amount), ['entry_id' => $entry->id]);

            return $entry;
        });
    }

    /** Payment voucher: Dr payable / Cr cash. */
    public function pay(MasterProfile $profile, float $amount, int $cashAccountId, string $date, ?string $note = null): JournalEntry
    {
        return DB::transaction(function () use ($profile, $amount, $cashAccountId, $date, $note) {
            $supplier = $this->parties->supplierFor($profile);
            $profile->addRole('supplier');

            $entry = app(PostSupplierPayment::class)->handle(
                $supplier, $this->accounts->require(LedgerAccounts::PAYABLE), $cashAccountId, $amount, $date,
                description: $note ?: "Payment to {$profile->display_name}",
            );

            $this->plans->applyPayment($profile, 'supplier', $amount, $entry->id);
            $profile->logActivity('payment', 'Payment made ' . Bdt::money($amount), ['entry_id' => $entry->id]);

            return $entry;
        });
    }

    /** Advance to a supplier before a bill exists: Dr Supplier Advance / Cr cash. */
    public function supplierAdvance(MasterProfile $profile, float $amount, int $cashAccountId, string $date, ?string $note = null): void
    {
        DB::transaction(function () use ($profile, $amount, $cashAccountId, $date, $note) {
            $supplier = $this->parties->supplierFor($profile);
            $profile->addRole('supplier');

            // Same as the Purchase module's advance invoice, so Supplier.balance
            // there stays in step with the ledger.
            $invoice = SupplierInvoice::create([
                'supplier_id'   => $supplier->id,
                'serial_number' => ($supplier->invoices()->max('serial_number') ?? 0) + 1,
                'type'          => SupplierInvoiceType::ADVANCE,
                'amount'        => $amount,
                'invoice_date'  => $date,
                'notes'         => $note,
            ]);

            $advance = app(PostSupplierAdvance::class)->handle(
                $supplier, $this->accounts->require(LedgerAccounts::SUPPLIER_ADVANCE), $cashAccountId, $amount, $date,
                supplierInvoiceId: $invoice->id,
                description: $note ?: "Advance paid to {$profile->display_name}",
            );

            $profile->logActivity('advance', 'Advance given ' . Bdt::money($amount), ['entry_id' => $advance->journal_entry_id]);
        });
    }

    /**
     * Contra voucher for a profile that is both customer and supplier:
     * Dr payable / Cr receivable for min(receivable, payable), applied to
     * the open invoices and bills oldest-first.
     */
    public function setOff(MasterProfile $profile, string $date): ?JournalEntry
    {
        $b = app(ProfileBalances::class)->forProfile($profile->id);
        $amount = round(min((float) $b->receivable, (float) $b->payable), 2);

        $customer = $profile->customer()->first();
        $supplier = $profile->supplier()->first();

        if ($amount <= 0 || ! $customer || ! $supplier) {
            return null;
        }

        return DB::transaction(function () use ($profile, $amount, $customer, $supplier, $date) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $date,
                'description'      => "Set-off receivable against payable — {$profile->display_name}",
                'transaction_type' => TransactionType::SET_OFF->value,
            ], [
                ['account_id' => $this->accounts->require(LedgerAccounts::PAYABLE), 'debit' => $amount, 'subledger_type' => Supplier::class, 'subledger_id' => $supplier->id],
                ['account_id' => $this->accounts->require(LedgerAccounts::RECEIVABLE), 'credit' => $amount, 'subledger_type' => Customer::class, 'subledger_id' => $customer->id],
            ]);

            $this->allocate(AccountsCustomerInvoice::where('customer_id', $customer->id), $entry, $amount);
            $this->allocate(AccountsSupplierBill::where('supplier_id', $supplier->id), $entry, $amount);

            $profile->logActivity('setoff', 'Set-off ' . Bdt::money($amount) . ' (receivable vs payable)', ['entry_id' => $entry->id]);

            return $entry;
        });
    }

    /** Owner capital in: Dr cash / Cr Owner Capital (tagged to the owner). */
    public function addCapital(MasterProfile $owner, float $amount, int $cashAccountId, string $date, ?string $note = null): JournalEntry
    {
        $entry = app(PostOwnerInvestment::class)->handle(
            $cashAccountId, $this->accounts->require(LedgerAccounts::CAPITAL), $amount, $date,
            $note ?: "Capital in — {$owner->display_name}", $owner->id,
        );
        $owner->logActivity('capital', 'Capital added ' . Bdt::money($amount), ['entry_id' => $entry->id]);

        return $entry;
    }

    /** Owner drawing: Dr Owner Drawings / Cr cash. Equity, never an expense. */
    public function drawing(MasterProfile $owner, float $amount, int $cashAccountId, string $date, ?string $note = null): JournalEntry
    {
        $entry = app(PostOwnerWithdrawal::class)->handle(
            $this->accounts->require(LedgerAccounts::DRAWINGS), $cashAccountId, $amount, $date,
            $note ?: "Drawing — {$owner->display_name}", $owner->id,
        );
        $owner->logActivity('drawing', 'Drawing ' . Bdt::money($amount), ['entry_id' => $entry->id]);

        return $entry;
    }

    /**
     * Opening balance from the profile form, dated "as of", against Opening
     * Balance Equity. receive = they owe us (receivable), pay = we owe them
     * (payable). An owner's opening capital is posted the same way.
     */
    public function openingBalance(MasterProfile $profile, float $amount, string $direction, string $date): ?JournalEntry
    {
        if ($amount <= 0) {
            return null;
        }

        $equity = $this->accounts->require(LedgerAccounts::OPENING_EQUITY);

        if ($direction === 'receive') {
            $customer = $profile->hasRole('customer') ? $this->parties->customerFor($profile) : null;
            $party = $customer
                ? ['subledger_type' => Customer::class, 'subledger_id' => $customer->id]
                : ['subledger_type' => MasterProfile::class, 'subledger_id' => $profile->id];
            $account = $profile->hasRole('employee') && ! $customer ? LedgerAccounts::SALARY_ADVANCE : LedgerAccounts::RECEIVABLE;

            $lines = [
                ['account_id' => $this->accounts->require($account), 'debit' => $amount] + $party,
                ['account_id' => $equity, 'credit' => $amount],
            ];
        } else {
            $supplier = $profile->hasRole('supplier') ? $this->parties->supplierFor($profile) : null;
            $party = $supplier
                ? ['subledger_type' => Supplier::class, 'subledger_id' => $supplier->id]
                : ['subledger_type' => MasterProfile::class, 'subledger_id' => $profile->id];
            $account = $profile->hasRole('employee') && ! $supplier ? LedgerAccounts::SALARY_PAYABLE : LedgerAccounts::PAYABLE;

            $lines = [
                ['account_id' => $equity, 'debit' => $amount],
                ['account_id' => $this->accounts->require($account), 'credit' => $amount] + $party,
            ];
        }

        $entry = $this->postJournalEntry->handle([
            'entry_date'       => $date,
            'description'      => "Opening balance — {$profile->display_name}",
            'transaction_type' => TransactionType::OPENING_BALANCE->value,
            'source_type'      => MasterProfile::class,
            'source_id'        => $profile->id,
            'purpose'          => "opening_{$direction}",
        ], $lines);

        $profile->logActivity('opening', 'Opening balance ' . Bdt::money($amount) . ($direction === 'receive' ? ' Dr' : ' Cr') . ' · ' . \Carbon\Carbon::parse($date)->format('d M Y'));

        return $entry;
    }

    /** Owner's opening capital from the form: Dr Opening Balance Equity / Cr Owner Capital. */
    /**
     * Owner's starting capital. With a cash / bank account the money comes
     * into it (Dr that account); without one it is already counted in the
     * opening balances (Dr Opening Balance Equity). Cr the owner's capital.
     */
    public function openingCapital(MasterProfile $owner, float $amount, string $date, ?int $cashAccountId = null): ?JournalEntry
    {
        if ($amount <= 0) {
            return null;
        }

        return $this->postJournalEntry->handle([
            'entry_date'       => $date,
            'description'      => "Opening capital — {$owner->display_name}",
            'transaction_type' => TransactionType::OWNER_INVESTMENT->value,
            'source_type'      => MasterProfile::class,
            'source_id'        => $owner->id,
            'purpose'          => 'opening_capital',
        ], [
            ['account_id' => $cashAccountId ?: $this->accounts->require(LedgerAccounts::OPENING_EQUITY), 'debit' => $amount],
            ['account_id' => $this->accounts->require(LedgerAccounts::CAPITAL), 'credit' => $amount, 'subledger_type' => MasterProfile::class, 'subledger_id' => $owner->id],
        ]);
    }

    /** Spread a set-off over open invoices / bills, oldest first. */
    protected function allocate($openItems, JournalEntry $entry, float $amount): void
    {
        $remaining = $amount;

        foreach ($openItems->whereIn('status', ['open', 'partial'])->oldest()->get() as $item) {
            if ($remaining <= 0) {
                break;
            }

            $apply = min($remaining, $item->amountDue());
            if ($apply <= 0) {
                continue;
            }

            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $entry->id,
                'allocatable_type'         => $item::class,
                'allocatable_id'           => $item->id,
                'amount'                   => $apply,
            ]);

            $item->increment('amount_allocated', $apply);
            $item->refresh();
            $item->update(['status' => $item->amountDue() <= 0.01 ? 'paid' : 'partial']);
            $remaining -= $apply;
        }
    }
}
