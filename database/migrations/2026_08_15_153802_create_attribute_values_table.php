<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();

            $table->string('value');
            $table->string('slug');

            $table->string('swatch_type', 50)->nullable();
            $table->string('swatch_value')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('sort_order');
            $table->unique(['attribute_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
