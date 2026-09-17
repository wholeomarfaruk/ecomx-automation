<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsSupplierAdvance;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;

/**
 * Case 4.5 — paying a supplier before a bill exists. Recorded as a
 * receivable-style asset (Supplier Advance) rather than an expense, wrapped
 * in an AccountsSupplierAdvance open item so ApplySupplierAdvance can draw
 * it down against a later bill — mirrors PostSupplierBill on the payable
 * side.
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
        ?int $supplierInvoiceId = null,
        ?string $description = null,
    ): AccountsSupplierAdvance {
        return DB::transaction(function () use ($supplier, $supplierAdvanceAccountId, $cashAccountId, $amount, $entryDate, $supplierInvoiceId, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Advance paid to {$supplier->name}",
                'transaction_type' => TransactionType::SUPPLIER_ADVANCE->value,
                'source_type'      => SupplierInvoice::class,
                'source_id'        => $supplierInvoiceId,
                'purpose'          => $supplierInvoiceId ? 'supplier_advance' : null,
            ], [
                [
                    'account_id'     => $supplierAdvanceAccountId,
                    'debit'          => $amount,
                    'subledger_type' => Supplier::class,
                    'subledger_id'   => $supplier->id,
                ],
                ['account_id' => $cashAccountId, 'credit' => $amount],
            ]);

            return AccountsSupplierAdvance::create([
                'supplier_id'         => $supplier->id,
                'supplier_invoice_id' => $supplierInvoiceId,
                'amount'              => $amount,
                'amount_applied'      => 0,
                'status'              => 'open',
                'journal_entry_id'    => $entry->id,
            ]);
        });
    }
}
