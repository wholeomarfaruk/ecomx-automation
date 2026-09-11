<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared by AR and AP allocation (Cases 3.4 and 4.3) — records how much
     * of one payment/journal entry was applied against which open item
     * (AccountsCustomerInvoice or AccountsSupplierBill).
     */
    public function up(): void
    {
        Schema::create('accounts_payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();

            $table->string('allocatable_type');
            $table->unsignedBigInteger('allocatable_id');

            $table->decimal('amount', 20, 2);

            $table->timestamps();

            $table->index(['allocatable_type', 'allocatable_id'], 'accounts_payment_allocations_allocatable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payment_allocations');
    }
};
