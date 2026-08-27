<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_event_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_event_id')->constrained('marketing_events')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot fields — deliberately duplicated from the product/variant at
            // event time, not references, so historical marketing data stays accurate
            // if the product's name/price/sku changes later.
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('item_id')->nullable();

            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->nullable();
            $table->decimal('total_value', 18, 4)->nullable();
            $table->string('currency', 3)->nullable();

            $table->unsignedInteger('position')->nullable();

            $table->json('item_data')->nullable();

            $table->timestamps();

            $table->index('marketing_event_id');
            $table->index(['product_id', 'created_at']);
            $table->index(['variant_id', 'created_at']);
            $table->index(['sku', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_event_items');
    }
};
