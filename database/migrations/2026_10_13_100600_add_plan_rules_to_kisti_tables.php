<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loans & EMI page: kisti plans carry their interest rule and a late rule
 * (reminder / late fee / block credit); a kisti remembers when its late fee
 * was charged so the daily job charges it once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->string('rate_type', 20)->default('none')->after('total');
            $table->decimal('rate', 8, 4)->nullable()->after('rate_type');
            $table->decimal('interest', 20, 2)->default(0)->after('rate');
            $table->string('late_rule', 20)->default('reminder')->after('support_rule');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->timestamp('late_fee_at')->nullable()->after('paid_at');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->string('late_rule', 20)->default('reminder')->after('support_rule');
        });
    }

    public function down(): void
    {
        Schema::table('loans', fn (Blueprint $t) => $t->dropColumn('late_rule'));
        Schema::table('installments', fn (Blueprint $t) => $t->dropColumn('late_fee_at'));
        Schema::table('installment_plans', fn (Blueprint $t) => $t->dropColumn(['rate_type', 'rate', 'interest', 'late_rule']));
    }
};
