<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('ps_id')->constrained('ps')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('zip_code_id')->constrained('zip_codes')->cascadeOnDelete();
            $table->foreignId('street_id')->constrained('streets')->cascadeOnDelete();

            $table->string('name', 150)->nullable();
            $table->string('local_name')->nullable();
            $table->string('code', 50)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['street_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
