<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "Order Processing" in Admin > Notifications > Events — drives the
     * auto message when an order moves to Processing (SendOrderProcessingSms).
     * SMS on by default with the order_processing template; inserted here,
     * not only in the seeder, so live sites get it.
     */
    public function up(): void
    {
        if (DB::table('notification_events')->where('event_key', 'order_processing')->exists()) {
            return;
        }

        DB::table('notification_events')->insert([
            'event_key' => 'order_processing',
            'label' => 'Order Processing',
            'channel_email' => false,
            'channel_sms' => true,
            'channel_push' => false,
            'channel_browser' => false,
            'channel_database' => false,
            'email_template_key' => null,
            'sms_template_key' => 'order_processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('notification_events')->where('event_key', 'order_processing')->delete();
    }
};
