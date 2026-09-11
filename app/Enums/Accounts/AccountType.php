<?php

namespace App\Enums\Accounts;

enum AccountType: string
{
    case ASSET     = 'asset';
    case LIABILITY = 'liability';
    case EQUITY    = 'equity';
    case INCOME    = 'income';
    case EXPENSE   = 'expense';

    /**
     * Assets and expenses grow with a debit; liabilities, equity and income
     * grow with a credit. Individual accounts (e.g. contra-asset Accumulated
     * Depreciation) can still override this via accounts.normal_balance.
     */
    public function defaultNormalBalance(): NormalBalance
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => NormalBalance::DEBIT,
            self::LIABILITY, self::EQUITY, self::INCOME => NormalBalance::CREDIT,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ASSET     => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY    => 'Equity',
            self::INCOME    => 'Income',
            self::EXPENSE   => 'Expense',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ASSET     => 'bg-blue-50 text-blue-600',
            self::LIABILITY => 'bg-red-50 text-red-500',
            self::EQUITY    => 'bg-purple-50 text-purple-600',
            self::INCOME    => 'bg-emerald-50 text-emerald-600',
            self::EXPENSE   => 'bg-orange-50 text-orange-600',
        };
    }
}
