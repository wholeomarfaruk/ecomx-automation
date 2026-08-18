<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 50)->default('simple')->after('status');
            // simple, variable, combo

            $table->boolean('combo_allowed')->default(false)->after('product_type');
            // whether this product can be used as a component inside another combo

            $table->decimal('combo_price', 20, 2)->nullable()->after('purchase_price');
            // default price used when this product is added as a combo component

            $table->index('product_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_type']);
            $table->dropColumn(['product_type', 'combo_allowed', 'combo_price']);
        });
    }
};
