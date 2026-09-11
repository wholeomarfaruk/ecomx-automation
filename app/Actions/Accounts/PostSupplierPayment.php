<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsPaymentAllocation;
use App\Models\AccountsSupplierBill;
use App\Models\JournalEntry;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Cases 4.2 (full/partial payment) and 4.3 (one payment split across
 * multiple bills) — mirrors PostCustomerPayment on the payable side.
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
    ): JournalEntry {
        return DB::transaction(function () use ($supplier, $payableAccountId, $cashAccountId, $amount, $entryDate, $allocations, $description) {
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
