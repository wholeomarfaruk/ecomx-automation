<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounts Panel dashboard sums Stock In / Stock Out by date range
     * (created_at) — without this index that's a full table scan.
     */
    public function up(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
