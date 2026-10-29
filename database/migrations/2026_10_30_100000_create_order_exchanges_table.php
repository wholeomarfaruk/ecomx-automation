<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exchanges inside an existing order — no new order: the customer sends
     * back some delivered units (order_exchange_items) and gets new ones
     * (order_items with exchange_id), carried by a second courier parcel
     * (courier_shipments.type = 'exchange') with its own status.
     *
     * courier_shipments.merchant_ref is the reference sent to the courier
     * (merchant_order_id / invoice) so a webhook that only carries it can be
     * matched to the right parcel when an order has more than one.
     *
     * Additive only: existing rows read as normal shipments / non-exchange lines.
     */
    public function up(): void
    {
        Schema::create('order_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->default(1);
            $table->string('status', 20)->default('requested')->index();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->decimal('charge_amount', 20, 2)->default(0);
            $table->foreignId('order_charge_id')->nullable()->constrained('order_charges')->nullOnDelete();
            $table->foreignId('courier_shipment_id')->nullable()->constrained('courier_shipments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'number']);
        });

        Schema::create('order_exchange_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_exchange_id')->constrained('order_exchanges')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->decimal('quantity', 20, 3);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('exchange_id')->nullable()->after('combo_id')->constrained('order_exchanges')->nullOnDelete();
        });

        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->string('type', 20)->default('normal')->after('courier_account_id');
            $table->string('merchant_ref', 64)->nullable()->after('consignment_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->dropIndex(['merchant_ref']);
            $table->dropColumn(['type', 'merchant_ref']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exchange_id');
        });

        Schema::dropIfExists('order_exchange_items');
        Schema::dropIfExists('order_exchanges');
    }
};
