<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Customer;
use App\Models\JournalEntry;

/**
 * Case 3.8 (overpayment branch) — a customer pays more than an invoice/
 * order requires. The excess is parked as a liability (Customer Credit)
 * rather than immediately refunded, so it can be applied to a future order
 * via ApplyCustomerCredit.
 */
class PostCustomerCreditIssue
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Customer $customer,
        int $cashAccountId,
        int $receivableAccountId,
        int $customerCreditAccountId,
        float $totalReceived,
        float $amountOwed,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        $creditAmount = round($totalReceived - $amountOwed, 2);

        $lines = [
            ['account_id' => $cashAccountId, 'debit' => $totalReceived],
        ];

        if ($amountOwed > 0) {
            $lines[] = [
                'account_id'     => $receivableAccountId,
                'credit'         => $amountOwed,
                'subledger_type' => Customer::class,
                'subledger_id'   => $customer->id,
            ];
        }

        $lines[] = [
            'account_id'     => $customerCreditAccountId,
            'credit'         => $creditAmount,
            'subledger_type' => Customer::class,
            'subledger_id'   => $customer->id,
        ];

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "Overpayment credited to {$customer->full_name}",
            'transaction_type' => TransactionType::CREDIT_NOTE->value,
        ], $lines);
    }
}
