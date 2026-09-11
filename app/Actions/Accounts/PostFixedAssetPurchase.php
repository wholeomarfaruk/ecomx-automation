<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\FixedAsset;
use Illuminate\Support\Facades\DB;

/**
 * Case 7.1 — buying a fixed asset is not a regular expense; it creates a
 * new asset record whose cost gets depreciated over its useful life
 * instead of hitting P&L immediately.
 */
class PostFixedAssetPurchase
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        string $name,
        int $categoryAccountId,
        float $cost,
        string $purchaseDate,
        int $cashAccountId,
        int $usefulLifeMonths,
        float $salvageValue = 0,
        string $method = 'straight_line',
        ?string $description = null,
    ): FixedAsset {
        return DB::transaction(function () use (
            $name, $categoryAccountId, $cost, $purchaseDate, $cashAccountId,
            $usefulLifeMonths, $salvageValue, $method, $description
        ) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $purchaseDate,
                'description'      => $description ?? "Fixed asset purchased — {$name}",
                'transaction_type' => TransactionType::ASSET_PURCHASE->value,
            ], [
                ['account_id' => $categoryAccountId, 'debit' => $cost],
                ['account_id' => $cashAccountId, 'credit' => $cost],
            ]);

            return FixedAsset::create([
                'name'                     => $name,
                'category_account_id'      => $categoryAccountId,
                'cost'                     => $cost,
                'purchase_date'            => $purchaseDate,
                'useful_life_months'       => $usefulLifeMonths,
                'salvage_value'            => $salvageValue,
                'method'                   => $method,
                'accumulated_depreciation' => 0,
                'status'                   => 'active',
                'journal_entry_id'         => $entry->id,
            ]);
        });
    }
}
