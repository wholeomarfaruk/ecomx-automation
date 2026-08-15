<?php

namespace Database\Seeders;

use App\Models\NotificationEvent;
use Illuminate\Database\Seeder;

class NotificationEventSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'event_key' => 'user_registered',
                'label' => 'User Registered (Admin-created)',
                'channel_email' => true,
                'channel_sms' => false,
                'channel_push' => false,
                'channel_browser' => false,
                'channel_database' => true,
                'email_template_key' => 'welcome',
                'sms_template_key' => null,
            ],
            [
                'event_key' => 'signup_registered',
                'label' => 'Signup Successful (Customer self-registration)',
                'channel_email' => true,
                'channel_sms' => false,
                'channel_push' => false,
                'channel_browser' => false,
                'channel_database' => true,
                'email_template_key' => 'signup_welcome',
                'sms_template_key' => null,
            ],
            [
                'event_key' => 'password_reset',
                'label' => 'Password Reset',
                'channel_email' => true,
                'channel_sms' => true,
                'channel_push' => false,
                'channel_browser' => false,
                'channel_database' => false,
                'email_template_key' => 'password_reset',
                'sms_template_key' => 'password_reset',
            ],
            [
                'event_key' => 'otp',
                'label' => 'OTP Verification',
                'channel_email' => true,
                'channel_sms' => true,
                'channel_push' => false,
                'channel_browser' => false,
                'channel_database' => false,
                'email_template_key' => 'otp',
                'sms_template_key' => 'otp',
            ],
            [
                'event_key' => 'login',
                'label' => 'New Login',
                'channel_email' => true,
                'channel_sms' => false,
                'channel_push' => false,
                'channel_browser' => true,
                'channel_database' => false,
                'email_template_key' => 'login',
                'sms_template_key' => 'login',
            ],
            [
                'event_key' => 'order_placed',
                'label' => 'Order Placed',
                'channel_email' => true,
                'channel_sms' => true,
                'channel_push' => true,
                'channel_browser' => true,
                'channel_database' => true,
                'email_template_key' => 'order_confirmation',
                'sms_template_key' => 'order_confirmation',
            ],
            [
                'event_key' => 'order_shipped',
                'label' => 'Order Shipped',
                'channel_email' => true,
                'channel_sms' => true,
                'channel_push' => true,
                'channel_browser' => true,
                'channel_database' => true,
                'email_template_key' => 'order_confirmation',
                'sms_template_key' => 'order_confirmation',
            ],
        ];

        foreach ($defaults as $event) {
            NotificationEvent::updateOrCreate(
                ['event_key' => $event['event_key']],
                $event
            );
        }
    }
}
