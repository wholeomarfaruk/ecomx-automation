<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A merchant's connected account for a given courier — credentials live
     * here (encrypted), never on the couriers table, so multiple accounts
     * per courier are possible (e.g. two SteadFast merchant accounts) and
     * one is flagged default per courier for auto-selection.
     */
    public function up(): void
    {
        Schema::create('courier_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->string('name');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamp('last_balance_check_at')->nullable();
            $table->decimal('last_balance', 12, 4)->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_accounts');
    }
};
