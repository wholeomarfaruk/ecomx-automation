<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 4.5 — an open item for money paid to a supplier before any bill
     * exists (Dr Supplier Advance / Cr Cash). Mirrors accounts_supplier_bills
     * so it can be drawn down (amount_applied/status) via the same
     * AccountsPaymentAllocation table a later bill uses, instead of a single
     * PO/invoice-level flag — a supplier advance can be applied across
     * several bills over time, same as a bill can be paid in installments.
     */
    public function up(): void
    {
        Schema::create('accounts_supplier_advances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();

            $table->decimal('amount', 20, 2);
            $table->decimal('amount_applied', 20, 2)->default(0);
            $table->enum('status', ['open', 'partial', 'applied'])->default('open');

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_supplier_advances');
    }
};
