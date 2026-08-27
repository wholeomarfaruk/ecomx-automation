<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_statistics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();

            $table->unsignedInteger('total_reviews')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);

            $table->unsignedInteger('rating_1_count')->default(0);
            $table->unsignedInteger('rating_2_count')->default(0);
            $table->unsignedInteger('rating_3_count')->default(0);
            $table->unsignedInteger('rating_4_count')->default(0);
            $table->unsignedInteger('rating_5_count')->default(0);

            $table->unsignedInteger('verified_reviews_count')->default(0);
            $table->unsignedInteger('reviews_with_media_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_statistics');
    }
};
