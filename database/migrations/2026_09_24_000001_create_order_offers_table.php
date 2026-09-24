<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which offers (App\Services\OfferService) an order was placed with,
        // and what each one saved — name is snapshotted so the record
        // survives the offer being edited or deleted later.
        Schema::create('order_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();

            $table->string('name');
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('shipping_discount', 20, 2)->default(0);

            $table->timestamps();

            $table->index('order_id');
            $table->index('offer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_offers');
    }
};
