<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('reorder_level', 20, 3)->default(0)->after('stock_quantity');
            $table->decimal('reorder_quantity', 20, 3)->default(0)->after('reorder_level');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['reorder_level', 'reorder_quantity']);
        });
    }
};
