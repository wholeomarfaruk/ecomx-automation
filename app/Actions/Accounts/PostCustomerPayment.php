<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\Customer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Cases 3.2 (full payment), 3.3 (partial payment), 3.4 (one payment split
 * across multiple invoices). One Dr cash-account / Cr AR journal entry for
 * the full amount received, then that amount is spread across the given
 * invoices oldest-first (or exactly as specified) via allocation rows —
 * mirroring how a business actually applies a payment.
 */
class PostCustomerPayment
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    /**
     * @param  array<int, float>  $allocations  invoice_id => amount to apply; omit to auto-allocate oldest-first
     */
    public function handle(
        Customer $customer,
        int $cashAccountId,
        int $receivableAccountId,
        float $amount,
        string $entryDate,
        array $allocations = [],
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use ($customer, $cashAccountId, $receivableAccountId, $amount, $entryDate, $allocations, $description) {
            $paymentEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Payment received from {$customer->full_name}",
                'transaction_type' => TransactionType::PAYMENT_RECEIPT->value,
            ], [
                ['account_id' => $cashAccountId, 'debit' => $amount],
                [
                    'account_id'     => $receivableAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
            ]);

            $allocations = $allocations ?: $this->autoAllocate($customer, $amount);

            foreach ($allocations as $invoiceId => $allocatedAmount) {
                if ($allocatedAmount <= 0) {
                    continue;
                }

                $invoice = AccountsCustomerInvoice::where('customer_id', $customer->id)->findOrFail($invoiceId);

                AccountsPaymentAllocation::create([
                    'payment_journal_entry_id' => $paymentEntry->id,
                    'allocatable_type'         => AccountsCustomerInvoice::class,
                    'allocatable_id'           => $invoice->id,
                    'amount'                   => $allocatedAmount,
                ]);

                $invoice->increment('amount_allocated', $allocatedAmount);
                $invoice->refresh();
                $invoice->update(['status' => $invoice->amountDue() <= 0.01 ? 'paid' : 'partial']);
            }

            return $paymentEntry;
        });
    }

    /**
     * @return array<int, float>
     */
    protected function autoAllocate(Customer $customer, float $amount): array
    {
        $openInvoices = AccountsCustomerInvoice::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'partial'])
            ->oldest()
            ->get();

        $remaining = $amount;
        $result = [];

        foreach ($openInvoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }

            $apply = min($remaining, $invoice->amountDue());
            if ($apply > 0) {
                $result[$invoice->id] = $apply;
                $remaining -= $apply;
            }
        }

        return $result;
    }
}
