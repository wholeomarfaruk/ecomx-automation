<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts Panel (handoff 2): per-account fields used by Cash & Bank and
 * Expenses, plus the system ledgers the new modules post to. Ledgers are
 * only added when the chart of accounts is already seeded; fresh installs
 * get them from AccountSeeder.
 */
return new class extends Migration
{
    /** code => [name, type, subtype, normal_balance, parent_code] */
    public const LEDGERS = [
        '4950' => ['Sales Rebate', 'income', 'contra_income', 'debit', null],
        '4150' => ['Late Fee Income', 'income', null, 'credit', null],
        '2450' => ['Rebate Payable', 'liability', 'payable', 'credit', null],
        '5150' => ['Packaging', 'expense', null, 'debit', '5100'],
        '5160' => ['Repairs & maintenance', 'expense', null, 'debit', '5100'],
        '5170' => ['Utilities', 'expense', null, 'debit', '5100'],
        '5180' => ['Office & misc', 'expense', null, 'debit', '5100'],
        '5810' => ['Commission Expense', 'expense', null, 'debit', null],
        '5900' => ['Cash Short / Excess', 'expense', null, 'debit', null],
        '5910' => ['Stock Loss', 'expense', null, 'debit', null],
    ];

    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('account_number', 60)->nullable()->after('name');
            $table->decimal('monthly_budget', 20, 2)->nullable()->after('opening_balance');
            $table->decimal('receipt_required_above', 20, 2)->nullable()->after('monthly_budget');
            $table->date('last_reconciled_at')->nullable()->after('receipt_required_above');
            $table->decimal('last_reconciled_balance', 20, 2)->nullable()->after('last_reconciled_at');
        });

        if (! DB::table('accounts')->exists()) {
            return;
        }

        $ids = DB::table('accounts')->pluck('id', 'code');

        foreach (self::LEDGERS as $code => [$name, $type, $subtype, $normal, $parent]) {
            if (isset($ids[$code])) {
                continue;
            }

            $ids[$code] = DB::table('accounts')->insertGetId([
                'code'               => $code,
                'name'               => $name,
                'type'               => $type,
                'subtype'            => $subtype,
                'normal_balance'     => $normal,
                'parent_id'          => $parent ? ($ids[$parent] ?? null) : null,
                'is_control_account' => $code === '2450',
                'is_system'          => $parent === null,
                'is_active'          => true,
                'opening_balance'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', array_keys(self::LEDGERS))
            ->whereNotExists(fn ($q) => $q->from('journal_entry_lines')->whereColumn('journal_entry_lines.account_id', 'accounts.id'))
            ->delete();

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'monthly_budget', 'receipt_required_above', 'last_reconciled_at', 'last_reconciled_balance']);
        });
    }
};
