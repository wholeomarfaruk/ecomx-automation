<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per courier booking attempt for an order. Kept separate from
     * `orders` (which only carries a flat "current snapshot" via its
     * courier_provider/courier_tracking_number/courier_status columns) so
     * an order can be re-booked with a different courier after a failed
     * attempt without losing the history of what was tried.
     */
    public function up(): void
    {
        Schema::create('courier_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers');
            $table->foreignId('courier_account_id')->constrained('courier_accounts');

            $table->string('shipment_id')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('consignment_id')->nullable();

            $table->string('status')->default('pending');
            $table->string('previous_status')->nullable();

            $table->decimal('cod_amount', 12, 2)->nullable();
            $table->decimal('delivery_charge', 12, 2)->nullable();
            $table->decimal('return_charge', 12, 2)->nullable();

            $table->timestamp('pickup_requested_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['courier_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_shipments');
    }
};
