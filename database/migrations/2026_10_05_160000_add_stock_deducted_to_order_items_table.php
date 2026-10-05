<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Inventory module off: how much of this line is currently taken
            // off the product's/variant's own stock_quantity (see
            // App\Services\Stock\OwnOrderStock). 0 while the module is on.
            $table->decimal('stock_deducted', 20, 3)->default(0)->after('delivered_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('stock_deducted');
        });
    }
};
