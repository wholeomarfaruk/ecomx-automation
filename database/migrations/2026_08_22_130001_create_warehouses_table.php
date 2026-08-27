<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            $table->string('status', 50)->default('active');
            // active, inactive

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index('branch_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
