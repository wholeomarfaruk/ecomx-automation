<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Seeds the system chart of accounts from docs/ecomX-accounts-cases.md
 * (Part 2). These rows are is_system=true — non-deletable, and their
 * code/type/normal_balance shouldn't be edited from the UI. Users add their
 * own cash/bank accounts, expense categories, and fixed-asset categories as
 * non-system children under the matching parent below.
 */
class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $dollarCurrencyId = Currency::where('code', 'USD')->value('id');

        $accounts = [
            // Assets
            ['code' => '1010', 'name' => 'Cash',                       'type' => 'asset',     'subtype' => 'cash'],
            ['code' => '1020', 'name' => 'Bank',                       'type' => 'asset',     'subtype' => 'bank'],
            ['code' => '1030', 'name' => 'Mobile Banking',             'type' => 'asset',     'subtype' => 'mobile_banking'],
            ['code' => '1040', 'name' => 'Dollar Card',                'type' => 'asset',     'subtype' => 'bank', 'currency_id' => $dollarCurrencyId],
            ['code' => '1050', 'name' => 'Courier COD Receivable',     'type' => 'asset',     'subtype' => 'receivable', 'is_control_account' => true],
            ['code' => '1060', 'name' => 'Gateway Receivable',         'type' => 'asset',     'subtype' => 'receivable', 'is_control_account' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable',        'type' => 'asset',     'subtype' => 'receivable', 'is_control_account' => true],
            ['code' => '1150', 'name' => 'Supplier Advance',           'type' => 'asset',     'subtype' => 'receivable', 'is_control_account' => true],
            ['code' => '1200', 'name' => 'Inventory',                  'type' => 'asset',     'subtype' => null],
            ['code' => '1500', 'name' => 'Fixed Assets',                'type' => 'asset',     'subtype' => 'fixed_asset'],
            ['code' => '1510', 'name' => 'Accumulated Depreciation',   'type' => 'asset',     'subtype' => 'contra_asset', 'normal_balance' => 'credit'],

            // Liabilities
            ['code' => '2100', 'name' => 'Accounts Payable',            'type' => 'liability', 'subtype' => 'payable', 'is_control_account' => true],
            ['code' => '2150', 'name' => 'Customer Credit',             'type' => 'liability', 'subtype' => 'payable', 'is_control_account' => true],
            ['code' => '2200', 'name' => 'Loan Payable',                'type' => 'liability', 'subtype' => 'payable', 'is_control_account' => true],
            ['code' => '2300', 'name' => 'VAT Payable',                 'type' => 'liability', 'subtype' => 'tax'],

            // Equity
            ['code' => '3000', 'name' => 'Owner Capital',               'type' => 'equity',    'subtype' => null],
            ['code' => '3100', 'name' => 'Owner Drawings',              'type' => 'equity',    'subtype' => 'contra_equity', 'normal_balance' => 'debit'],
            ['code' => '3900', 'name' => 'Retained Earnings',           'type' => 'equity',    'subtype' => null],
            ['code' => '3950', 'name' => 'Opening Balance Equity',      'type' => 'equity',    'subtype' => null],

            // Income
            ['code' => '4000', 'name' => 'Sales',                       'type' => 'income',    'subtype' => null],
            ['code' => '4100', 'name' => 'Other Income',                'type' => 'income',    'subtype' => null],
            ['code' => '4200', 'name' => 'Gain on Disposal',            'type' => 'income',    'subtype' => null],
            ['code' => '4900', 'name' => 'Sales Return',                'type' => 'income',    'subtype' => 'contra_income', 'normal_balance' => 'debit'],

            // Expenses
            ['code' => '5000', 'name' => 'Cost of Goods Sold',           'type' => 'expense',   'subtype' => 'cogs'],
            ['code' => '5100', 'name' => 'Expenses',                     'type' => 'expense',   'subtype' => null],
            ['code' => '5300', 'name' => 'Transfer / Bank Charge',       'type' => 'expense',   'subtype' => 'fee'],
            ['code' => '5310', 'name' => 'Gateway Fee',                  'type' => 'expense',   'subtype' => 'fee'],
            ['code' => '5320', 'name' => 'Conversion Fee',               'type' => 'expense',   'subtype' => 'fee'],
            ['code' => '5400', 'name' => 'Interest Expense',             'type' => 'expense',   'subtype' => null],
            ['code' => '5500', 'name' => 'Depreciation Expense',         'type' => 'expense',   'subtype' => null],
            ['code' => '5600', 'name' => 'Bad Debt Expense',             'type' => 'expense',   'subtype' => null],
            ['code' => '5700', 'name' => 'Loss on Disposal',             'type' => 'expense',   'subtype' => null],
        ];

        $expenseSubCategories = [
            ['code' => '5110', 'name' => 'Advertising',       'parent_code' => '5100'],
            ['code' => '5120', 'name' => 'Courier Expense',   'parent_code' => '5100'],
            ['code' => '5130', 'name' => 'Salary',             'parent_code' => '5100'],
            ['code' => '5140', 'name' => 'Rent',               'parent_code' => '5100'],
        ];

        $codeToId = [];

        foreach ($accounts as $account) {
            $normalBalance = $account['normal_balance']
                ?? \App\Enums\Accounts\AccountType::from($account['type'])->defaultNormalBalance()->value;

            $model = Account::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name'               => $account['name'],
                    'type'               => $account['type'],
                    'subtype'            => $account['subtype'] ?? null,
                    'normal_balance'     => $normalBalance,
                    'is_control_account' => $account['is_control_account'] ?? false,
                    'is_system'          => true,
                    'is_active'          => true,
                    'currency_id'        => $account['currency_id'] ?? null,
                ]
            );

            $codeToId[$account['code']] = $model->id;
        }

        foreach ($expenseSubCategories as $sub) {
            Account::updateOrCreate(
                ['code' => $sub['code']],
                [
                    'name'           => $sub['name'],
                    'type'           => 'expense',
                    'subtype'        => null,
                    'normal_balance' => 'debit',
                    'parent_id'      => $codeToId[$sub['parent_code']],
                    'is_system'      => false,
                    'is_active'      => true,
                ]
            );
        }
    }
}
