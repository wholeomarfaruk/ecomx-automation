<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kisti plans for customers (sale on installments) and suppliers (pay a
 * purchase in parts). Loans keep their own loan_repayment_schedules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('reference')->nullable();
            $table->string('frequency', 20);
            $table->unsignedSmallInteger('count');
            $table->decimal('amount', 20, 2);
            $table->decimal('total', 20, 2);
            $table->date('start_date');
            $table->json('custom_offsets')->nullable();
            $table->string('support_rule', 20)->default('next_kisti');
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['master_profile_id', 'status']);
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained('installment_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('no');
            $table->date('due_date');
            $table->decimal('amount', 20, 2);
            $table->decimal('adjusted_amount', 20, 2);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->decimal('support_amount', 20, 2)->default(0);
            $table->string('status', 20)->default('upcoming');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->index(['installment_plan_id', 'no']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
        Schema::dropIfExists('installment_plans');
    }
};
