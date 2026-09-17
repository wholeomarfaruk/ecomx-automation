<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets one invoice line reference the exact PurchaseOrderItem it bills
     * against, so "how much of this PO item has been invoiced so far" can be
     * computed the same way received-so-far is computed for receiving — by
     * summing linked invoice item amounts/quantities, not a single PO-level
     * status flag. Nullable: manual invoice lines (no PO involved) leave it
     * null, and a PO item can be billed across several invoices (partial
     * deliveries), so this is not unique.
     */
    public function up(): void
    {
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('purchase_order_items')
                ->nullOnDelete();

            $table->index('purchase_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_item_id']);
            $table->dropIndex(['purchase_order_item_id']);
            $table->dropColumn('purchase_order_item_id');
        });
    }
};
