<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 6.2 — a recurring expense template (rent, internet). The
     * scheduler advances next_run_date each time PostRecurringExpenseRun
     * fires for it; it never posts more than one journal entry per due
     * date thanks to that same Action's idempotent purpose key.
     */
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();

            $table->decimal('amount', 20, 2);
            $table->string('cadence'); // monthly, weekly, yearly
            $table->date('next_run_date');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('next_run_date');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
