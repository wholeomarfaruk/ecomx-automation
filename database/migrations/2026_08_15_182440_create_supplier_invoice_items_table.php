<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();

            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->string('name');
            $table->decimal('quantity', 20, 3)->nullable();
            $table->decimal('unit_price', 20, 2)->nullable();
            $table->decimal('amount', 20, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_items');
    }
};
