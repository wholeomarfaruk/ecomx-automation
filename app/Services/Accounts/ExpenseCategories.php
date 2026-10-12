<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\AccountType;
use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * Expense categories are expense ledgers: the children of "Expenses"
 * (5100, salary excepted — payroll has its own screen) plus "Bank & MFS
 * charges" (5300). Each can carry a monthly budget.
 */
class ExpenseCategories
{
    public const PARENT = '5100';
    public const CHARGES = '5300';
    public const EXCLUDED = ['5130'];

    /** @return Collection<int, Account> */
    public static function all(): Collection
    {
        $parentId = Account::where('code', self::PARENT)->value('id');

        return Account::query()
            ->where('is_active', true)
            ->where('type', AccountType::EXPENSE->value)
            ->whereNotIn('code', self::EXCLUDED)
            ->where(fn ($q) => $q->where('parent_id', $parentId)->orWhere('code', self::CHARGES))
            ->orderBy('code')
            ->get();
    }

    public static function create(string $name): Account
    {
        $parent = Account::where('code', self::PARENT)->firstOrFail();
        $code = (int) Account::where('parent_id', $parent->id)->max('code') + 1;

        while (Account::withTrashed()->where('code', (string) $code)->exists()) {
            $code++;
        }

        return Account::create([
            'code'           => (string) $code,
            'name'           => $name,
            'type'           => AccountType::EXPENSE->value,
            'normal_balance' => 'debit',
            'parent_id'      => $parent->id,
            'is_system'      => false,
            'is_active'      => true,
        ]);
    }
}
