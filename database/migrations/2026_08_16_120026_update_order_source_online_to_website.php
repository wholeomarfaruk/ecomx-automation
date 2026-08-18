<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('source', 'online')->update(['source' => 'website']);

        DB::statement("ALTER TABLE orders MODIFY source VARCHAR(255) NOT NULL DEFAULT 'website'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY source VARCHAR(20) NOT NULL DEFAULT 'online'");

        DB::table('orders')->where('source', 'website')->update(['source' => 'online']);
    }
};
