<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thin wrapper around the existing SupplierInvoice — gives the ledger
     * a real double-entry-backed open item to allocate payments against
     * without touching SupplierInvoice's own serial-number/deletion rules
     * (see app/Models/SupplierInvoice.php). One row per SupplierInvoice of
     * type "purchase" that the Accounts module has posted a journal entry
     * for.
     */
    public function up(): void
    {
        Schema::create('accounts_supplier_bills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();

            $table->decimal('amount', 20, 2);
            $table->decimal('amount_allocated', 20, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'paid'])->default('open');

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_supplier_bills');
    }
};
