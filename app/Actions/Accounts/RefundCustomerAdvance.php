<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerAdvance;
use App\Models\Customer;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

/**
 * Refunds an order that was cancelled before ever completing, when the
 * customer had already paid an advance — there's no sale to reverse (none
 * was ever posted), so this simply draws down the Customer Advance
 * liability instead of Sales Return like RefundOrder does for a
 * post-completion refund. Mirrors RefundOrder's cash-vs-store-credit
 * branching exactly, just pointed at Customer Advance (2160) as the debit
 * side.
 */
class RefundCustomerAdvance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        AccountsCustomerAdvance $advance,
        float $amount,
        string $entryDate,
        bool $asStoreCredit,
        ?int $cashAccountId,
        int $customerAdvanceAccountId,
        int $customerCreditAccountId,
        ?string $description = null,
    ): JournalEntry {
        return DB::transaction(function () use (
            $advance, $amount, $entryDate, $asStoreCredit, $cashAccountId,
            $customerAdvanceAccountId, $customerCreditAccountId, $description
        ) {
            $customer = $advance->customer;
            $creditOrCashAccountId = $asStoreCredit ? $customerCreditAccountId : $cashAccountId;

            if (! $creditOrCashAccountId) {
                throw new \InvalidArgumentException('A cash/bank account is required for a cash refund.');
            }

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Advance refund — Order #{$advance->order_id}" . ($asStoreCredit ? ' (store credit)' : ''),
                'transaction_type' => ($asStoreCredit ? TransactionType::CREDIT_NOTE : TransactionType::REFUND)->value,
            ], [
                [
                    'account_id'     => $customerAdvanceAccountId,
                    'debit'          => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
                [
                    'account_id'     => $creditOrCashAccountId,
                    'credit'         => $amount,
                    'subledger_type' => $asStoreCredit ? Customer::class : null,
                    'subledger_id'   => $asStoreCredit ? $customer->id : null,
                ],
            ]);

            $applied = min($amount, $advance->amountRemaining());

            if ($applied > 0) {
                $advance->increment('amount_applied', $applied);
                $advance->refresh();
            }

            $advance->update(['status' => $advance->amountRemaining() <= 0.01 ? 'refunded' : 'partial']);

            return $entry;
        });
    }
}
