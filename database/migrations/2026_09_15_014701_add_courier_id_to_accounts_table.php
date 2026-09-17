<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a "Courier Cash" sub-account (under 1045) to the specific
     * courier it tracks COD money held-but-not-yet-settled for — lets code
     * resolve "which account do I debit when this courier's webhook says
     * money was collected" directly from the Courier model instead of a
     * hardcoded/guessed account code.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('courier_id')->nullable()->after('parent_id')->constrained('couriers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('courier_id');
        });
    }
};
