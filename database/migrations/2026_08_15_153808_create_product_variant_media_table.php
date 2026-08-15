<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('files')->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_variant_id', 'media_id']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_media');
    }
};
