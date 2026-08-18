<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('source', 20)->default('online');
            // online, pos, admin, api

            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('pending');
            $table->string('fulfillment_status', 20)->default('unfulfilled');

            $table->string('currency', 10)->default('BDT');

            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('shipping_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->decimal('due_amount', 20, 2)->default(0);

            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->foreignId('billing_address_id')->nullable()->constrained('delivery_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('delivery_addresses')->nullOnDelete();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Courier / delivery tracking
            $table->string('courier_provider', 100)->nullable();
            $table->string('courier_tracking_number', 150)->nullable();
            $table->decimal('courier_charge', 20, 2)->nullable();
            $table->string('courier_status', 30)->nullable();
            $table->json('courier_meta')->nullable();
            $table->timestamp('courier_status_updated_at')->nullable();

            $table->timestamps();

            $table->index('customer_id');
            $table->index('source');
            $table->index('status');
            $table->index('payment_status');
            $table->index('fulfillment_status');
            $table->index('placed_at');
            $table->index('courier_tracking_number');
            $table->index('courier_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
