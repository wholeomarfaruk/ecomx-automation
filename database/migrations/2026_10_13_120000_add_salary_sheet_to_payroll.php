<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll becomes a salary sheet: a run starts as a Draft that can be edited
 * per employee (allowances, overtime, bonus, incentive, arrears, absent
 * days, advance recovery, tax, fines) and only posts to the books when
 * approved. Existing runs were already posted, so they stay "prepared".
 * Adds the "Tax deducted (TDS) payable" ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('other_deducted', 20, 2)->default(0)->after('advance_deducted');
            $table->unsignedTinyInteger('working_days')->nullable()->after('due_on');
            $table->text('note')->nullable()->after('bonus_note');
            $table->foreignId('posted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('posted_by');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->boolean('include')->default(true)->after('master_profile_id');
            $table->decimal('house_rent', 20, 2)->default(0)->after('salary');
            $table->decimal('medical', 20, 2)->default(0)->after('house_rent');
            $table->decimal('conveyance', 20, 2)->default(0)->after('medical');
            $table->decimal('other_allowance', 20, 2)->default(0)->after('conveyance');
            $table->decimal('ot_hours', 10, 2)->default(0)->after('other_allowance');
            $table->decimal('ot_rate', 20, 2)->default(0)->after('ot_hours');
            $table->decimal('ot_amount', 20, 2)->default(0)->after('ot_rate');
            $table->decimal('incentive', 20, 2)->default(0)->after('bonus');
            $table->decimal('arrears', 20, 2)->default(0)->after('incentive');
            $table->decimal('gross', 20, 2)->default(0)->after('arrears');
            $table->decimal('working_days', 5, 2)->default(30)->after('gross');
            $table->decimal('absent_days', 5, 2)->default(0)->after('working_days');
            $table->decimal('absent_deduction', 20, 2)->default(0)->after('absent_days');
            $table->decimal('tax', 20, 2)->default(0)->after('advance_deduction');
            $table->decimal('other_deduction', 20, 2)->default(0)->after('tax');
            $table->decimal('total_deduction', 20, 2)->default(0)->after('other_deduction');
            $table->json('advance_cuts')->nullable()->after('status');
            $table->string('note')->nullable()->after('advance_cuts');
        });

        // Existing items: earnings = salary + bonus.
        DB::table('payroll_items')->update(['gross' => DB::raw('salary + bonus'), 'total_deduction' => DB::raw('advance_deduction')]);

        if (DB::table('accounts')->exists() && ! DB::table('accounts')->where('code', '2410')->exists()) {
            DB::table('accounts')->insert([
                'code' => '2410', 'name' => 'Tax Deducted (TDS) Payable', 'type' => 'liability', 'subtype' => 'tax', 'normal_balance' => 'credit',
                'parent_id' => null, 'is_control_account' => false, 'is_system' => true, 'is_active' => true, 'opening_balance' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn(['include', 'house_rent', 'medical', 'conveyance', 'other_allowance', 'ot_hours', 'ot_rate', 'ot_amount', 'incentive', 'arrears',
                'gross', 'working_days', 'absent_days', 'absent_deduction', 'tax', 'other_deduction', 'total_deduction', 'advance_cuts', 'note']);
        });
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_by');
            $table->dropColumn(['other_deducted', 'working_days', 'note', 'posted_at']);
        });
    }
};
