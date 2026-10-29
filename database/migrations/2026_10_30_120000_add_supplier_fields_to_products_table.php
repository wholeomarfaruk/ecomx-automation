<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a product comes from — all optional, admin-only (hidden from the
     * product's JSON): the supplier record, what the supplier calls it,
     * their photo of it, and a link to their page / listing.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('brand_id')->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_product_name')->nullable()->after('supplier_id');
            $table->foreignId('supplier_image_id')->nullable()->after('supplier_product_name')->constrained('files')->nullOnDelete();
            $table->string('supplier_link', 1000)->nullable()->after('supplier_image_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_image_id');
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['supplier_product_name', 'supplier_link']);
        });
    }
};
