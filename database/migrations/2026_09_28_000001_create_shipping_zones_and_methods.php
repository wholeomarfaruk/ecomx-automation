<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // Stable key the checkout submits and the Offer module's
            // "Shipping Method" condition matches against (e.g. dhaka, outside).
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('delivery_time', 100)->nullable();
            $table->string('rate_type', 30)->default('flat');
            $table->json('rate_config')->nullable();
            $table->decimal('free_over', 20, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->nullable()->after('shipping_discount')
                ->constrained('shipping_zones')->nullOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_zone_id')
                ->constrained('shipping_methods')->nullOnDelete();
            // Zone/method names and the charge breakdown at order time, so later
            // rate edits never change what a past order shows.
            $table->json('shipping_meta')->nullable()->after('shipping_method_id');
        });

        // Same areas and charges the checkouts had hard-coded, so nothing
        // changes for customers until an admin edits them.
        $now = now();

        foreach ([['Inside Dhaka', 'dhaka', 70, 1], ['Outside Dhaka', 'outside', 130, 2]] as [$name, $code, $charge, $order]) {
            $zoneId = DB::table('shipping_zones')->insertGetId([
                'name'       => $name,
                'code'       => $code,
                'is_active'  => true,
                'sort_order' => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('shipping_methods')->insert([
                'shipping_zone_id' => $zoneId,
                'name'             => 'Standard Delivery',
                'rate_type'        => 'flat',
                'rate_config'      => json_encode(['amount' => $charge]),
                'is_active'        => true,
                'sort_order'       => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropConstrainedForeignId('shipping_zone_id');
            $table->dropColumn('shipping_meta');
        });

        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('shipping_zones');
    }
};
