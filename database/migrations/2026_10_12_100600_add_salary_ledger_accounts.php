<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Payroll ledgers for the Employees page. Only added when the chart of
 * accounts has been seeded (fresh installs get them from AccountSeeder).
 */
return new class extends Migration
{
    protected array $accounts = [
        ['code' => '1160', 'name' => 'Salary Advance', 'type' => 'asset', 'subtype' => 'receivable', 'normal_balance' => 'debit'],
        ['code' => '2400', 'name' => 'Salary Payable', 'type' => 'liability', 'subtype' => 'payable', 'normal_balance' => 'credit'],
    ];

    public function up(): void
    {
        if (! DB::table('accounts')->exists()) {
            return;
        }

        foreach ($this->accounts as $account) {
            if (DB::table('accounts')->where('code', $account['code'])->exists()) {
                continue;
            }

            DB::table('accounts')->insert($account + [
                'is_control_account' => true,
                'is_system'          => true,
                'is_active'          => true,
                'opening_balance'    => 0,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('code', ['1160', '2400'])
            ->whereNotExists(fn ($q) => $q->from('journal_entry_lines')->whereColumn('journal_entry_lines.account_id', 'accounts.id'))
            ->delete();
    }
};
