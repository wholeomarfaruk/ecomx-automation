<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_combo_items', function (Blueprint $table) {
            $table->boolean('allow_variant')->default(false)->after('variant_id');
            // when true, the customer may choose the variant (size/color) for
            // this component; when false, it is locked to variant_id
        });
    }

    public function down(): void
    {
        Schema::table('product_combo_items', function (Blueprint $table) {
            $table->dropColumn('allow_variant');
        });
    }
};
