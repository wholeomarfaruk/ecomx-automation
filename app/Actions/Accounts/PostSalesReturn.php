<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\JournalEntry;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Case 3.6 — a delivered order the customer returns, where the goods are
 * resellable: reverses the recognized sale (Sales Return against
 * Receivable/Refund) and puts the cost back into Inventory out of COGS.
 * RTO (never delivered) is handled separately by PostRtoFee, since no sale
 * was ever recognized for it in the first place — see the spec's note.
 */
class PostSalesReturn
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Order $order,
        int $salesReturnAccountId,
        int $receivableOrCashAccountId,
        int $inventoryAccountId,
        int $cogsAccountId,
        float $saleAmount,
        float $costAmount,
        string $entryDate,
        ?string $description = null,
    ): array {
        return DB::transaction(function () use (
            $order, $salesReturnAccountId, $receivableOrCashAccountId,
            $inventoryAccountId, $cogsAccountId, $saleAmount, $costAmount, $entryDate, $description
        ) {
            $returnEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description ?? "Sales return — Order #{$order->id}",
                'transaction_type' => TransactionType::SALES_RETURN->value,
                'source_type'      => Order::class,
                'source_id'        => $order->id,
                'purpose'          => 'sales_return',
            ], [
                ['account_id' => $salesReturnAccountId, 'debit' => $saleAmount],
                ['account_id' => $receivableOrCashAccountId, 'credit' => $saleAmount],
            ]);

            $stockEntry = null;
            if ($costAmount > 0) {
                $stockEntry = $this->postJournalEntry->handle([
                    'entry_date'       => $entryDate,
                    'description'      => $description ?? "Stock returned — Order #{$order->id}",
                    'transaction_type' => TransactionType::SALES_RETURN->value,
                    'source_type'      => Order::class,
                    'source_id'        => $order->id,
                    'purpose'          => 'sales_return_stock',
                ], [
                    ['account_id' => $inventoryAccountId, 'debit' => $costAmount],
                    ['account_id' => $cogsAccountId, 'credit' => $costAmount],
                ]);
            }

            return ['return' => $returnEntry, 'stock' => $stockEntry];
        });
    }
}
