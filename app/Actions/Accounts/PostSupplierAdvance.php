<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\Supplier;

/**
 * Case 4.5 — paying a supplier before a bill exists. Recorded as a
 * receivable-style asset (Supplier Advance) rather than an expense; a
 * later PostSupplierBill/PostSupplierPayment application draws it down.
 */
class PostSupplierAdvance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Supplier $supplier,
        int $supplierAdvanceAccountId,
        int $cashAccountId,
        float $amount,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? "Advance paid to {$supplier->name}",
            'transaction_type' => TransactionType::SUPPLIER_ADVANCE->value,
        ], [
            [
                'account_id'     => $supplierAdvanceAccountId,
                'debit'          => $amount,
                'subledger_type' => Supplier::class,
                'subledger_id'   => $supplier->id,
            ],
            ['account_id' => $cashAccountId, 'credit' => $amount],
        ]);
    }
}
