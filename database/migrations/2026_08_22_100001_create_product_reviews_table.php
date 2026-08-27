<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('author_type', 20)->default('customer');
            // customer, admin

            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_phone', 50)->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('source', 30)->default('website');
            // website, admin, facebook, whatsapp, phone, import

            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            $table->boolean('is_verified_purchase')->default(false);

            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
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

            $table->unsignedInteger('helpful_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_id');
            $table->index('order_item_id');

            $table->index(['product_id', 'status']);
            $table->index(['product_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
