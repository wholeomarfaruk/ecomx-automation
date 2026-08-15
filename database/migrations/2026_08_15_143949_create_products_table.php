<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('short_description')->nullable();
            $table->text('description')->nullable();

            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

            $table->string('status', 50)->default('active');

            $table->boolean('featured')->default(false);

            $table->string('stock_status', 50)->default('in_stock');
            // in_stock, out_of_stock, low_stock, backorder

            $table->decimal('price', 20, 2)->nullable();
            $table->decimal('sale_price', 20, 2)->nullable();
            $table->decimal('purchase_price', 20, 2)->nullable();

            $table->foreignId('featured_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->json('image_ids')->nullable();
            $table->json('video_ids')->nullable();

            $table->decimal('weight', 20, 3)->nullable();
            $table->decimal('length', 20, 3)->nullable();
            $table->decimal('width', 20, 3)->nullable();
            $table->decimal('height', 20, 3)->nullable();

            // seo
            $table->foreignId('meta_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('featured');
            $table->index('stock_status');
            $table->index('sort_order');
        });

        Schema::create('product_category_pivot', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'category_id']);
            $table->index('category_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_pivot');
        Schema::dropIfExists('products');
    }
};
