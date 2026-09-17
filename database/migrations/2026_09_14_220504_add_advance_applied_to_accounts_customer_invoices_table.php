<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_customer_invoices', function (Blueprint $table) {
            $table->decimal('advance_applied', 20, 2)->default(0)->after('amount_allocated');
        });
    }

    public function down(): void
    {
        Schema::table('accounts_customer_invoices', function (Blueprint $table) {
            $table->dropColumn('advance_applied');
        });
    }
};
