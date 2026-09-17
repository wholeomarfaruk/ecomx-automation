<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerAdvance;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Money paid by a customer before their order is completed — no sale/
 * invoice exists yet to receivable against, so this posts Dr Cash / Cr
 * Customer Advance (a liability) instead of PostCustomerPayment's Dr Cash /
 * Cr Accounts Receivable. Wrapped in an AccountsCustomerAdvance open item so
 * ApplyCustomerAdvance can draw it down once the order completes and a real
 * invoice exists — mirrors PostSupplierAdvance on the payable side.
 */
class PostCustomerAdvance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Customer $customer,
        Order $order,
        int $customerAdvanceAccountId,
        int $cashAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): AccountsCustomerAdvance {
        return DB::transaction(function () use ($customer, $order, $customerAdvanceAccountId, $cashAccountId, $amount, $entryDate, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Advance received — Order #{$order->id}",
                'transaction_type' => TransactionType::CUSTOMER_ADVANCE->value,
            ], [
                ['account_id' => $cashAccountId, 'debit' => $amount],
                [
                    'account_id'     => $customerAdvanceAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Customer::class,
                    'subledger_id'   => $customer->id,
                ],
            ]);

            return AccountsCustomerAdvance::create([
                'customer_id'      => $customer->id,
                'order_id'         => $order->id,
                'amount'           => $amount,
                'amount_applied'   => 0,
                'status'           => 'held',
                'journal_entry_id' => $entry->id,
            ]);
        });
    }
}
