<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 5.2 — principal and interest are tracked as separate amounts
     * (Golden Rule #4: never mix them), even though they're paid together
     * in a single journal entry with two distinct debit lines.
     */
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('loan_repayment_schedules')->nullOnDelete();

            $table->decimal('principal_amount', 20, 2);
            $table->decimal('interest_amount', 20, 2)->default(0);

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->date('paid_at');

            $table->timestamps();

            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
