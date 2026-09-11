<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\JournalEntry;

/**
 * Case 9.1 — seed starting balances for any set of accounts (cash, stock,
 * receivables, payables, loans, ...). The caller supplies one signed amount
 * per account (positive = debit-normal increase, e.g. an asset; negative =
 * credit-normal increase, e.g. a liability) and this posts one line per
 * account plus a single balancing line against Opening Balance Equity so
 * the whole entry satisfies double-entry by construction.
 */
class PostOpeningBalance
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    /**
     * @param  array<int, float>  $signedAmountsByAccountId  account_id => signed opening balance (debit-positive)
     */
    public function handle(
        array $signedAmountsByAccountId,
        int $openingBalanceEquityAccountId,
        string $entryDate,
        ?string $description = null,
    ): JournalEntry {
        $lines = [];
        $plug = 0.0;

        foreach ($signedAmountsByAccountId as $accountId => $signedAmount) {
            if ($signedAmount == 0) {
                continue;
            }

            $lines[] = $signedAmount > 0
                ? ['account_id' => $accountId, 'debit' => $signedAmount]
                : ['account_id' => $accountId, 'credit' => abs($signedAmount)];

            $plug -= $signedAmount;
        }

        $lines[] = $plug > 0
            ? ['account_id' => $openingBalanceEquityAccountId, 'debit' => $plug]
            : ['account_id' => $openingBalanceEquityAccountId, 'credit' => abs($plug)];

        return $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => $description ?? 'Opening balance',
            'transaction_type' => TransactionType::OPENING_BALANCE->value,
            'purpose'          => 'opening_balance',
            'source_type'      => Account::class,
            'source_id'        => $openingBalanceEquityAccountId,
        ], $lines);
    }
}
