<?php

namespace App\Services\Profiles;

use App\Models\Account;
use RuntimeException;

/**
 * The chart-of-accounts rows the Master Profiles pages post to and read
 * from, looked up by their system code once per request.
 */
class LedgerAccounts
{
    public const RECEIVABLE       = '1100';
    public const SUPPLIER_ADVANCE = '1150';
    public const SALARY_ADVANCE   = '1160';
    public const PAYABLE          = '2100';
    public const CUSTOMER_CREDIT  = '2150';
    public const CUSTOMER_ADVANCE = '2160';
    public const SALARY_PAYABLE   = '2400';
    public const LOAN_PAYABLE     = '2200';
    public const CAPITAL          = '3000';
    public const DRAWINGS         = '3100';
    public const OPENING_EQUITY   = '3950';
    public const INTEREST_EXPENSE = '5400';
    public const SALARY_EXPENSE   = '5130';

    public const CASH_SUBTYPES = ['cash', 'bank', 'mobile_banking'];

    /** @var array<string, int>|null */
    protected ?array $ids = null;

    /** Account id by code, 0 when the chart hasn't been seeded with it. */
    public function id(string $code): int
    {
        $this->ids ??= Account::query()->whereNotNull('code')->pluck('id', 'code')->map(fn ($id) => (int) $id)->all();

        return $this->ids[$code] ?? 0;
    }

    public function require(string $code): int
    {
        return $this->id($code) ?: throw new RuntimeException("Ledger account {$code} is missing — run the AccountSeeder.");
    }

    /** Cash, bank and mobile-banking accounts money can move through, for the voucher modals. */
    public function cashAccounts(): array
    {
        return Account::query()
            ->where('is_active', true)
            ->whereIn('subtype', self::CASH_SUBTYPES)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'subtype'])
            ->map(fn (Account $a) => ['id' => $a->id, 'name' => $a->name, 'subtype' => $a->subtype])
            ->all();
    }
}
