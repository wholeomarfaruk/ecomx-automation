<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();

            $table->string('media_type', 20);
            // image, video

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('product_review_id');
            $table->index('file_id');
            $table->index(['product_review_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_media');
    }
};
