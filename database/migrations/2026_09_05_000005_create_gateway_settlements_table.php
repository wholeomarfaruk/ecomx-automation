<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 11.1 — an online payment gateway (bKash/SSLCommerz) settles a
     * batch of collections into the bank, net of its fee.
     * gateway_account_id is a non-system child of the system "Gateway
     * Receivable" (1060) account, one per configured gateway, following
     * the same pattern as a new bank account under "Bank" (1020).
     */
    public function up(): void
    {
        Schema::create('gateway_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gateway_account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('settled_at');
            $table->decimal('gross_amount', 20, 2);
            $table->decimal('fee_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2);

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();

            $table->index(['gateway_account_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_settlements');
    }
};
