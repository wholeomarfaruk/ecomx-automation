<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsPaymentAllocation;
use App\Models\AccountsSupplierAdvance;
use App\Models\AccountsSupplierBill;
use App\Models\JournalEntry;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * Draws down an open supplier advance (Case 4.5's "later the bill arrives
 * and gets adjusted") against one or more open bills. No cash moves — the
 * advance asset is credited and Accounts Payable is debited, same shape as
 * ApplyCustomerCredit on the receivables side. One AccountsPaymentAllocation
 * row is recorded against each side (the advance and the bill) so both
 * open-item balances stay auditable back to this single journal entry.
 */
class ApplySupplierAdvance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    /**
     * @param  array<int, float>  $allocations  bill_id => amount to apply; omit to auto-allocate oldest-first, capped by the advance's remaining balance
     */
    public function handle(
        AccountsSupplierAdvance $advance,
        int $payableAccountId,
        string $entryDate,
        array $allocations = [],
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use ($advance, $payableAccountId, $entryDate, $allocations, $description) {
            $supplier = $advance->supplier;

            $allocations = $allocations ?: $this->autoAllocate($advance, $supplier);
            $amount = round(array_sum($allocations), 2);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Nothing to allocate — no open bills or the advance is fully applied.');
            }

            if ($amount > $advance->amountRemaining() + 0.01) {
                throw new \InvalidArgumentException('Allocation exceeds the advance\'s remaining balance.');
            }

            $advanceAccountId = $advance->journalEntry->lines()->where('debit', '>', 0)->value('account_id');

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Advance applied — {$supplier->name}",
                'transaction_type' => TransactionType::SUPPLIER_ADVANCE->value,
            ], [
                [
                    'account_id'     => $payableAccountId,
                    'debit'          => $amount,
                    'subledger_type' => Supplier::class,
                    'subledger_id'   => $supplier->id,
                ],
                [
                    'account_id'     => $advanceAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Supplier::class,
                    'subledger_id'   => $supplier->id,
                ],
            ]);

            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $entry->id,
                'allocatable_type'         => AccountsSupplierAdvance::class,
                'allocatable_id'           => $advance->id,
                'amount'                   => $amount,
            ]);

            $advance->increment('amount_applied', $amount);
            $advance->refresh();
            $advance->update(['status' => $advance->amountRemaining() <= 0.01 ? 'applied' : 'partial']);

            foreach ($allocations as $billId => $allocatedAmount) {
                if ($allocatedAmount <= 0) {
                    continue;
                }

                $bill = AccountsSupplierBill::where('supplier_id', $supplier->id)->findOrFail($billId);

                AccountsPaymentAllocation::create([
                    'payment_journal_entry_id' => $entry->id,
                    'allocatable_type'         => AccountsSupplierBill::class,
                    'allocatable_id'           => $bill->id,
                    'amount'                   => $allocatedAmount,
                ]);

                $bill->increment('amount_allocated', $allocatedAmount);
                $bill->refresh();
                $bill->update(['status' => $bill->amountDue() <= 0.01 ? 'paid' : 'partial']);
            }

            return $entry;
        });
    }

    /**
     * @return array<int, float>
     */
    protected function autoAllocate(AccountsSupplierAdvance $advance, Supplier $supplier): array
    {
        $openBills = AccountsSupplierBill::where('supplier_id', $supplier->id)
            ->whereIn('status', ['open', 'partial'])
            ->oldest()
            ->get();

        $remaining = $advance->amountRemaining();
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
