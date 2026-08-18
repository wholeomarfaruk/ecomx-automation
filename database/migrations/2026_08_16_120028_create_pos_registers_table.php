<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_registers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('code', 50);
            $table->string('name', 100);

            $table->string('status', 50)->default('active');
            // active, inactive

            $table->timestamps();

            $table->index('branch_id');
            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_registers');
    }
};
