<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer-side twin of accounts_supplier_advances — money paid by a
     * customer before their order is completed (no sale/invoice exists yet
     * to allocate against). Drawn down via ApplyCustomerAdvance once the
     * order completes and an AccountsCustomerInvoice exists, same shape
     * ApplySupplierAdvance already uses on the payable side.
     */
    public function up(): void
    {
        Schema::create('accounts_customer_advances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->decimal('amount', 20, 2);
            $table->decimal('amount_applied', 20, 2)->default(0);
            $table->string('status', 20)->default('held'); // held | partial | applied | refunded

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_customer_advances');
    }
};
