<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();

            $table->string('status', 50)->default('active');
            // active, inactive

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
