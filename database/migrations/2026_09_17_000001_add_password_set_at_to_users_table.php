<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A storefront customer auto-registered at checkout gets a random,
     * unusable password (see Checkout::createDeliveryProfile() and the
     * User::create() call that follows it) — they've never actually set
     * one. This column is the only reliable signal for that: null means
     * "still on the random checkout password," set means the customer (or
     * an admin) has chosen a real one, so the account page knows whether
     * to ask for their current password before changing it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
