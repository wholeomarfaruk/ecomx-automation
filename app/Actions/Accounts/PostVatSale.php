<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Customer;
use App\Models\JournalEntry;

/**
 * Case 12.1 — a VAT-inclusive sale. VAT collected is not revenue; it is a
 * liability owed to the tax authority (VAT Payable), separate from Sales.
 * This posts only the sale-recognition side; COGS still goes through
 * PostSaleWithCogs's cogsAmount handling if this sale also needs one
 * (kept as a distinct Action since not every business/every sale is
 * VAT-registered).
 */
class PostVatSale
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $receivableOrCashAccountId,
        int $salesAccountId,
        int $vatPayableAccountId,
        float $netAmount,
        float $vatAmount,
        string $entryDate,
        ?int $customerId = null,
        ?string $description = null,
    ): JournalEntry {
        $debitLine = ['account_id' => $receivableOrCashAccountId, 'debit' => $netAmount + $vatAmount];

        if ($customerId) {
            $debitLine['subledger_type'] = Customer::class;
            $debitLine['subledger_id'] = $customerId;
        }

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? 'VAT-inclusive sale',
            'transaction_type' => TransactionType::VAT_SALE->value,
        ], [
            $debitLine,
            ['account_id' => $salesAccountId, 'credit' => $netAmount],
            ['account_id' => $vatPayableAccountId, 'credit' => $vatAmount],
        ]);
    }
}
