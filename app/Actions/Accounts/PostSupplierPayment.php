<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Enums\Purchase\SupplierInvoiceType;
use App\Models\AccountsPaymentAllocation;
use App\Models\AccountsSupplierBill;
use App\Models\JournalEntry;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;

/**
 * Cases 4.2 (full/partial payment) and 4.3 (one payment split across
 * multiple bills) — mirrors PostCustomerPayment on the payable side.
 *
 * Supplier.balance (the Purchase module's own running total, updated via
 * SupplierInvoice model events) and AccountsSupplierBill (the Accounts
 * module's real open-item ledger) are two independent "what's owed"
 * figures. Pass $recordSupplierInvoice = true so a single call here keeps
 * both in sync — the alternative of creating the SupplierInvoice at the
 * call site and posting separately is how the two silently drifted apart
 * before this was noticed.
 */
class PostSupplierPayment
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    /**
     * @param  array<int, float>  $allocations  bill_id => amount to apply; omit to auto-allocate oldest-first
     */
    public function handle(
        Supplier $supplier,
        int $payableAccountId,
        int $cashAccountId,
        float $amount,
        string $entryDate,
        array $allocations = [],
        ?string $description = null,
        bool $recordSupplierInvoice = true,
        ?string $invoiceNumber = null,
    ): JournalEntry {
        return DB::transaction(function () use ($supplier, $payableAccountId, $cashAccountId, $amount, $entryDate, $allocations, $description, $recordSupplierInvoice, $invoiceNumber) {
            $paymentEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Payment to {$supplier->name}",
                'transaction_type' => TransactionType::SUPPLIER_PAYMENT->value,
            ], [
                [
                    'account_id'     => $payableAccountId,
                    'debit'          => $amount,
                    'subledger_type' => Supplier::class,
                    'subledger_id'   => $supplier->id,
                ],
                ['account_id' => $cashAccountId, 'credit' => $amount],
            ]);

            $allocations = $allocations ?: $this->autoAllocate($supplier, $amount);

            foreach ($allocations as $billId => $allocatedAmount) {
                if ($allocatedAmount <= 0) {
                    continue;
                }

                $bill = AccountsSupplierBill::where('supplier_id', $supplier->id)->findOrFail($billId);

                AccountsPaymentAllocation::create([
                    'payment_journal_entry_id' => $paymentEntry->id,
                    'allocatable_type'         => AccountsSupplierBill::class,
                    'allocatable_id'           => $bill->id,
                    'amount'                   => $allocatedAmount,
                ]);

                $bill->increment('amount_allocated', $allocatedAmount);
                $bill->refresh();
                $bill->update(['status' => $bill->amountDue() <= 0.01 ? 'paid' : 'partial']);
            }

            if ($recordSupplierInvoice) {
                $serial = ($supplier->invoices()->max('serial_number') ?? 0) + 1;

                SupplierInvoice::create([
                    'supplier_id'    => $supplier->id,
                    'serial_number'  => $serial,
                    'invoice_number' => $invoiceNumber,
                    'type'           => SupplierInvoiceType::PAYMENT,
                    'amount'         => $amount,
                    'invoice_date'   => $entryDate,
                ]);
            }

            return $paymentEntry;
        });
    }

    /**
     * @return array<int, float>
     */
    protected function autoAllocate(Supplier $supplier, float $amount): array
    {
        $openBills = AccountsSupplierBill::where('supplier_id', $supplier->id)
            ->whereIn('status', ['open', 'partial'])
            ->oldest()
            ->get();

        $remaining = $amount;
        $result = [];

        foreach ($openBills as $bill) {
            if ($remaining <= 0) {
                break;
            }

            $apply = min($remaining, $bill->amountDue());
            if ($apply > 0) {
                $result[$bill->id] = $apply;
                $remaining -= $apply;
            }
        }

        return $result;
    }
}
