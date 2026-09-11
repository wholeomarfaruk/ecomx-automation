<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 2.2 — a single reconciliation "run" for one cash/bank account
     * against one bank statement. Individual matched/unmatched lines live
     * in bank_reconciliation_lines.
     */
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 20, 2);
            $table->decimal('book_balance', 20, 2);

            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');

            $table->timestamps();

            $table->index(['account_id', 'statement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
