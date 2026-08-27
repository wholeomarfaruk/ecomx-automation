<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_review_replies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
            $table->foreignId('parent_reply_id')->nullable()->constrained('product_review_replies')->nullOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('author_type', 20);
            // customer, admin

            $table->text('comment');

            $table->string('status', 30)->default('pending');
            // pending, approved, rejected, hidden

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->text('hidden_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_review_id');
            $table->index('parent_reply_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index(['product_review_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_review_replies');
    }
};
