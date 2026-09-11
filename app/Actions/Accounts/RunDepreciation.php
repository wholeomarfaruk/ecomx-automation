<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use Illuminate\Support\Facades\DB;

/**
 * Case 7.2 — posts one month's straight-line depreciation for a fixed
 * asset. Idempotent per (asset, period) via the fixed_asset_depreciation_
 * entries unique index and PostJournalEntry's own duplicate guard, so
 * re-running a month's depreciation job twice is a no-op rather than
 * double-charging the expense (Golden Rule #8).
 */
class RunDepreciation
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        FixedAsset $asset,
        string $period,
        int $depreciationExpenseAccountId,
        int $accumulatedDepreciationAccountId,
        ?string $description = null,
    ): ?FixedAssetDepreciationEntry {
        $periodStart = \Illuminate\Support\Carbon::parse($period)->startOfMonth()->toDateString();

        if (FixedAssetDepreciationEntry::where('fixed_asset_id', $asset->id)->where('period', $periodStart)->exists()) {
            return null;
        }

        $amount = $asset->monthlyDepreciationAmount();

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($asset, $periodStart, $depreciationExpenseAccountId, $accumulatedDepreciationAccountId, $amount, $description) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $periodStart,
                'description'      => $description ?? "Depreciation — {$asset->name} ({$periodStart})",
                'transaction_type' => TransactionType::DEPRECIATION->value,
                'source_type'      => FixedAsset::class,
                'source_id'        => $asset->id,
                'purpose'          => "depreciation_{$periodStart}",
            ], [
                ['account_id' => $depreciationExpenseAccountId, 'debit' => $amount],
                ['account_id' => $accumulatedDepreciationAccountId, 'credit' => $amount],
            ]);

            $depreciationEntry = FixedAssetDepreciationEntry::create([
                'fixed_asset_id'   => $asset->id,
                'period'           => $periodStart,
                'amount'           => $amount,
                'journal_entry_id' => $entry->id,
            ]);

            $asset->increment('accumulated_depreciation', $amount);

            return $depreciationEntry;
        });
    }
}
