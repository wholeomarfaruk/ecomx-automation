<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\GatewaySettlement;
use Illuminate\Support\Facades\DB;

/**
 * Case 11.1 — an online payment gateway settles collected payments into
 * the bank, net of its fee. Structurally identical to PostCodSettlement
 * but against the Gateway Receivable control account instead of Courier
 * COD Receivable.
 */
class PostGatewaySettlement
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        int $gatewayAccountId,
        float $grossAmount,
        float $feeAmount,
        string $settledAt,
        int $bankAccountId,
        int $gatewayFeeExpenseAccountId,
        ?string $description = null,
    ): GatewaySettlement {
        $netAmount = round($grossAmount - $feeAmount, 2);

        return DB::transaction(function () use (
            $gatewayAccountId, $grossAmount, $feeAmount, $netAmount, $settledAt,
            $bankAccountId, $gatewayFeeExpenseAccountId, $description
        ) {
            $lines = [
                ['account_id' => $bankAccountId, 'debit' => $netAmount],
            ];

            if ($feeAmount > 0) {
                $lines[] = ['account_id' => $gatewayFeeExpenseAccountId, 'debit' => $feeAmount];
            }

            $lines[] = ['account_id' => $gatewayAccountId, 'credit' => $grossAmount];

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $settledAt,
                'description'      => $description ?? 'Gateway settlement',
                'transaction_type' => TransactionType::GATEWAY_SETTLEMENT->value,
            ], $lines);

            return GatewaySettlement::create([
                'gateway_account_id' => $gatewayAccountId,
                'settled_at'         => $settledAt,
                'gross_amount'       => $grossAmount,
                'fee_amount'         => $feeAmount,
                'net_amount'         => $netAmount,
                'journal_entry_id'   => $entry->id,
            ]);
        });
    }
}
