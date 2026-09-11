<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per loan taken. loan_payable_account_id points at a
     * non-system child of the system "Loan Payable" (2200) account, so
     * each loan gets its own sub-ledger balance the same way a new bank
     * account is a child of "Bank" (1020) — see AccountSeeder.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('lender')->nullable();
            $table->decimal('principal', 20, 2);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->date('start_date');
            $table->unsignedSmallInteger('term_months')->nullable();

            $table->foreignId('loan_payable_account_id')->constrained('accounts')->restrictOnDelete();

            $table->enum('status', ['active', 'closed'])->default('active');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
