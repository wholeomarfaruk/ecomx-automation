<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\FixedAsset;
use App\Models\FixedAssetDisposal;
use Illuminate\Support\Facades\DB;

/**
 * Case 7.3 — retiring/selling a fixed asset. Removes the asset and its
 * accumulated depreciation from the books, records whatever cash came in,
 * and automatically computes the gain or loss as the plug that balances
 * the entry — proceeds above book value is a gain (credit to Gain on
 * Disposal), below is a loss (debit to Loss on Disposal).
 */
class DisposeFixedAsset
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        FixedAsset $asset,
        float $proceeds,
        string $disposedAt,
        int $cashAccountId,
        int $accumulatedDepreciationAccountId,
        int $lossOnDisposalAccountId,
        int $gainOnDisposalAccountId,
        ?string $description = null,
    ): FixedAssetDisposal {
        $bookValue = $asset->bookValue();
        $gainLoss = round($proceeds - $bookValue, 2);

        return DB::transaction(function () use (
            $asset, $proceeds, $disposedAt, $cashAccountId, $accumulatedDepreciationAccountId,
            $lossOnDisposalAccountId, $gainOnDisposalAccountId, $description, $bookValue, $gainLoss
        ) {
            $lines = [];

            if ($proceeds > 0) {
                $lines[] = ['account_id' => $cashAccountId, 'debit' => $proceeds];
            }

            if ((float) $asset->accumulated_depreciation > 0) {
                $lines[] = ['account_id' => $accumulatedDepreciationAccountId, 'debit' => (float) $asset->accumulated_depreciation];
            }

            if ($gainLoss < 0) {
                $lines[] = ['account_id' => $lossOnDisposalAccountId, 'debit' => abs($gainLoss)];
            }

            $lines[] = ['account_id' => $asset->category_account_id, 'credit' => (float) $asset->cost];

            if ($gainLoss > 0) {
                $lines[] = ['account_id' => $gainOnDisposalAccountId, 'credit' => $gainLoss];
            }

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $disposedAt,
                'description'      => $description ?? "Asset disposed — {$asset->name}",
                'transaction_type' => TransactionType::ASSET_DISPOSAL->value,
                'source_type'      => FixedAsset::class,
                'source_id'        => $asset->id,
                'purpose'          => 'disposal',
            ], $lines);

            $disposal = FixedAssetDisposal::create([
                'fixed_asset_id'    => $asset->id,
                'disposed_at'       => $disposedAt,
                'proceeds'          => $proceeds,
                'book_value'        => $bookValue,
                'gain_loss_amount'  => $gainLoss,
                'journal_entry_id'  => $entry->id,
            ]);

            $asset->update(['status' => 'disposed']);

            return $disposal;
        });
    }
}
