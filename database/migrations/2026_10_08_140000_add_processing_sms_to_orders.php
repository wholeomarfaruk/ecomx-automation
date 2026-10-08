<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SendOrderProcessingSms texts the customer once when an order first
     * reaches Processing (e.g. right after courier booking) — the timestamp
     * stops a Processing → Pending → Processing flip from texting twice.
     * The template is inserted here (not only the seeder) so live sites get it.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('processing_sms_sent_at')->nullable()->after('cancelled_at');
        });

        if (! DB::table('sms_templates')->where('key', 'order_processing')->exists()) {
            DB::table('sms_templates')->insert([
                'key' => 'order_processing',
                'label' => 'Order Processing (auto)',
                'body' => 'Dear {customer_name}, your order #{order_id} is being processed. Due: {due} Tk. Track: {website_tracking_url}',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('processing_sms_sent_at');
        });

        DB::table('sms_templates')->where('key', 'order_processing')->delete();
    }
};
