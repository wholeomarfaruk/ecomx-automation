<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named extra charges on an order (gift wrap, COD fee, urgent
        // delivery, …) — added from the admin order page. orders.
        // charges_amount caches their sum for totals/listing (Order::
        // recalculateTotals()); posted as Other Income at completion.
        Schema::create('order_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('label');
            $table->decimal('amount', 20, 2)->default(0);
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('charges_amount', 20, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('charges_amount');
        });

        Schema::dropIfExists('order_charges');
    }
};
