<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_helpful_votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('vote_type', 20)->default('helpful');
            // helpful, not_helpful

            $table->timestamp('created_at')->nullable();

            $table->index('product_review_id');
            $table->index('customer_id');
            $table->unique(['product_review_id', 'customer_id'], 'review_helpful_votes_review_customer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_helpful_votes');
    }
};
