<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Loans & EMI: link each loan to its lender profile and store its kisti
 * rule. Schedules gain a number, the amount actually due after a support
 * (over)payment on the previous kisti, and what was paid against them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('master_profile_id')->nullable()->after('id')->constrained('master_profiles')->nullOnDelete();
            $table->string('lender_type', 20)->nullable()->after('lender');
            $table->decimal('total_payable', 20, 2)->nullable()->after('principal');
            $table->string('rate_type', 20)->default('none')->after('interest_rate');
            $table->string('frequency', 20)->default('monthly')->after('rate_type');
            $table->unsignedSmallInteger('installments')->nullable()->after('frequency');
            $table->json('custom_offsets')->nullable()->after('installments');
            $table->string('support_rule', 20)->default('next_kisti')->after('custom_offsets');
            $table->string('note')->nullable()->after('support_rule');
        });

        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('installment_no')->nullable()->after('loan_id');
            $table->decimal('adjusted_amount', 20, 2)->nullable()->after('interest_due');
            $table->decimal('paid_amount', 20, 2)->default(0)->after('adjusted_amount');
            $table->decimal('support_amount', 20, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('loan_repayment_schedules', function (Blueprint $table) {
            $table->dropColumn(['installment_no', 'adjusted_amount', 'paid_amount', 'support_amount']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('master_profile_id');
            $table->dropColumn(['lender_type', 'total_payable', 'rate_type', 'frequency', 'installments', 'custom_offsets', 'support_rule', 'note']);
        });
    }
};
