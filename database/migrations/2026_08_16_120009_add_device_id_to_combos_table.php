<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->foreignId('device_id')->nullable()->after('customer_id')->constrained('devices')->nullOnDelete();

            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('combos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_id');
        });
    }
};
