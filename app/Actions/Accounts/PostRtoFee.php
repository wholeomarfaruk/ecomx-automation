<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\Order;

/**
 * Case 3.6 (RTO branch) — an order that never reached the customer (Return
 * To Origin). No sale was ever recognized for it, so there's nothing to
 * reverse; only the courier's return fee is a real expense. A negative $fee
 * reverses part of an earlier one (the charge was corrected down).
 */
class PostRtoFee
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Order $order,
        int $courierExpenseAccountId,
        int $paidFromAccountId,
        float $fee,
        string $entryDate,
        ?string $description = null,
        ?string $purpose = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "RTO fee — Order #{$order->id}",
            'transaction_type' => TransactionType::SALES_RETURN->value,
            'source_type'      => Order::class,
            'source_id'        => $order->id,
            'purpose'          => $purpose ?? 'rto_fee',
        ], $fee >= 0 ? [
            ['account_id' => $courierExpenseAccountId, 'debit' => $fee],
            ['account_id' => $paidFromAccountId, 'credit' => $fee],
        ] : [
            ['account_id' => $paidFromAccountId, 'debit' => -$fee],
            ['account_id' => $courierExpenseAccountId, 'credit' => -$fee],
        ]);
    }
}
