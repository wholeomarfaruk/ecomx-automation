<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\Supplier;

/**
 * Case 4.4 — returning stock to a supplier. If the bill is still unpaid,
 * this reduces Accounts Payable; if it was already paid, the caller should
 * instead pass the Supplier Advance account (a receivable) since the
 * business is now owed money/credit back — the spec calls this out as two
 * distinct cases with the same Cr Inventory side.
 */
class PostPurchaseReturn
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Supplier $supplier,
        int $payableOrAdvanceAccountId,
        int $inventoryAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "Purchase return — {$supplier->name}",
            'transaction_type' => TransactionType::PURCHASE_RETURN->value,
        ], [
            [
                'account_id'     => $payableOrAdvanceAccountId,
                'debit'          => $amount,
                'subledger_type' => Supplier::class,
                'subledger_id'   => $supplier->id,
            ],
            ['account_id' => $inventoryAccountId, 'credit' => $amount],
        ]);
    }
}
