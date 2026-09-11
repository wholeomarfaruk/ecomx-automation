<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Courier;
use App\Models\CourierCodSettlement;
use Illuminate\Support\Facades\DB;

/**
 * Case 10.2 — a courier remits collected COD, net of its fee. Draws down
 * the Courier COD Receivable control account (built up per Case 10.1 when
 * COD orders were shipped) and records the fee as an expense.
 */
class PostCodSettlement
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Courier $courier,
        float $grossAmount,
        float $feeAmount,
        string $settledAt,
        int $bankAccountId,
        int $courierCodReceivableAccountId,
        int $courierExpenseAccountId,
        ?string $description = null,
    ): CourierCodSettlement {
        $netAmount = round($grossAmount - $feeAmount, 2);

        return DB::transaction(function () use (
            $courier, $grossAmount, $feeAmount, $netAmount, $settledAt,
            $bankAccountId, $courierCodReceivableAccountId, $courierExpenseAccountId, $description
        ) {
            $lines = [
                ['account_id' => $bankAccountId, 'debit' => $netAmount],
            ];

            if ($feeAmount > 0) {
                $lines[] = ['account_id' => $courierExpenseAccountId, 'debit' => $feeAmount];
            }

            $lines[] = ['account_id' => $courierCodReceivableAccountId, 'credit' => $grossAmount];

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $settledAt,
                'description'      => $description ?? "COD settlement — {$courier->name}",
                'transaction_type' => TransactionType::COD_SETTLEMENT->value,
            ], $lines);

            return CourierCodSettlement::create([
                'courier_id'        => $courier->id,
                'settled_at'        => $settledAt,
                'gross_amount'      => $grossAmount,
                'fee_amount'        => $feeAmount,
                'net_amount'        => $netAmount,
                'journal_entry_id'  => $entry->id,
            ]);
        });
    }
}
