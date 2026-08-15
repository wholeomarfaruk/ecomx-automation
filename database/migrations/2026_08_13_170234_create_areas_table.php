<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('ps_id')->constrained('ps')->cascadeOnDelete();

            $table->string('name');
            $table->string('local_name')->nullable();
            $table->string('code')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['city_id', 'name']);
            $table->unique(['city_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
