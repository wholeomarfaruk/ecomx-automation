<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_discount', 20, 2)->default(0)->after('shipping_amount');

            $table->foreignId('coupon_id')->nullable()->after('shipping_address_id')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 100)->nullable()->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['shipping_discount', 'coupon_code']);
        });
    }
};
