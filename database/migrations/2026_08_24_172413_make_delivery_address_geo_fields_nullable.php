<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront checkout only collects free-text address + a coarse
 * delivery-area choice (Dhaka / outside Dhaka) — it has no location picker,
 * and the ps/areas/zip_codes/streets tables this schema references don't
 * even have Eloquent models in this codebase yet. Rather than fabricate
 * placeholder rows to satisfy NOT NULL constraints, these geo fields become
 * nullable: populated when they can genuinely be resolved from what the
 * customer typed, left null otherwise. full_address always carries the
 * real text regardless.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_addresses', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->change();
            $table->foreignId('state_id')->nullable()->change();
            $table->foreignId('city_id')->nullable()->change();
            $table->foreignId('ps_id')->nullable()->change();
            $table->foreignId('area_id')->nullable()->change();
            $table->foreignId('zip_code_id')->nullable()->change();
            $table->foreignId('street_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_addresses', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable(false)->change();
            $table->foreignId('state_id')->nullable(false)->change();
            $table->foreignId('city_id')->nullable(false)->change();
            $table->foreignId('ps_id')->nullable(false)->change();
            $table->foreignId('area_id')->nullable(false)->change();
            $table->foreignId('zip_code_id')->nullable(false)->change();
            $table->foreignId('street_id')->nullable(false)->change();
        });
    }
};
