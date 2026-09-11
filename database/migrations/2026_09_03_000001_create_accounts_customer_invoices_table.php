<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Open-item tracking for Accounts Receivable (Case 3.4 — one payment
     * split across multiple invoices). Not every Order creates one of
     * these (cash-on-the-spot orders never touch AR), so this is a
     * separate table rather than a column on orders.
     */
    public function up(): void
    {
        Schema::create('accounts_customer_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->string('invoice_number')->unique();
            $table->decimal('amount', 20, 2);
            $table->decimal('amount_allocated', 20, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid', 'written_off'])->default('open');

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_customer_invoices');
    }
};
