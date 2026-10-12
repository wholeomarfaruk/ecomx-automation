<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->decimal('monthly_deduction', 20, 2)->default(0);
            $table->decimal('balance', 20, 2);
            $table->date('given_on');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->index(['master_profile_id', 'balance']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique();
            $table->decimal('gross', 20, 2)->default(0);
            $table->decimal('bonus', 20, 2)->default(0);
            $table->string('bonus_note')->nullable();
            $table->decimal('advance_deducted', 20, 2)->default(0);
            $table->decimal('net', 20, 2)->default(0);
            $table->decimal('paid', 20, 2)->default(0);
            $table->string('status', 20)->default('prepared');
            $table->date('due_on')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->decimal('salary', 20, 2)->default(0);
            $table->decimal('bonus', 20, 2)->default(0);
            $table->decimal('advance_deduction', 20, 2)->default(0);
            $table->decimal('net', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->timestamps();
            $table->unique(['payroll_run_id', 'master_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_advances');
    }
};
