<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsSupplierBill;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;

/**
 * Case 4.1 — stock arrives on credit from a supplier. Posts the ledger
 * entry (Dr Inventory / Cr Accounts Payable) and wraps it in an
 * AccountsSupplierBill open item for payment allocation (Case 4.3),
 * optionally linked to the existing SupplierInvoice this bill corresponds
 * to (see plan decision: extend SupplierInvoice, don't replace it).
 */
class PostSupplierBill
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Supplier $supplier,
        int $inventoryAccountId,
        int $payableAccountId,
        float $amount,
        string $entryDate,
        ?int $supplierInvoiceId = null,
        ?string $description = null,
    ): AccountsSupplierBill {
        return DB::transaction(function () use ($supplier, $inventoryAccountId, $payableAccountId, $amount, $entryDate, $supplierInvoiceId, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Purchase from {$supplier->name}",
                'transaction_type' => TransactionType::SUPPLIER_BILL->value,
                'source_type'      => SupplierInvoice::class,
                'source_id'        => $supplierInvoiceId,
                'purpose'          => $supplierInvoiceId ? 'supplier_bill' : null,
            ], [
                ['account_id' => $inventoryAccountId, 'debit' => $amount],
                [
                    'account_id'     => $payableAccountId,
                    'credit'         => $amount,
                    'subledger_type' => Supplier::class,
                    'subledger_id'   => $supplier->id,
                ],
            ]);

            return AccountsSupplierBill::create([
                'supplier_id'          => $supplier->id,
                'supplier_invoice_id'  => $supplierInvoiceId,
                'amount'               => $amount,
                'amount_allocated'     => 0,
                'status'               => 'open',
                'journal_entry_id'     => $entry->id,
            ]);
        });
    }
}
