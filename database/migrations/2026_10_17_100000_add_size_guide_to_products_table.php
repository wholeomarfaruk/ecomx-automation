<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-product size guide (rich text, e.g. a size chart table) shown in
     * the storefront's "Size guide" modal.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('size_guide')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size_guide');
        });
    }
};
