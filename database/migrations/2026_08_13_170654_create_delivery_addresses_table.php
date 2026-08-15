<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('address_type')->nullable();

            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('alternative_phone', 20)->nullable();

            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();

            $table->foreignId('ps_id')->constrained('ps')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('zip_code_id')->constrained('zip_codes')->cascadeOnDelete();
            $table->foreignId('street_id')->constrained('streets')->cascadeOnDelete();
            $table->foreignId('house_id')->nullable()->constrained('houses')->nullOnDelete();

            $table->text('full_address')->nullable();
            $table->text('delivery_note')->nullable();

            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();

            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_addresses');
    }
};
