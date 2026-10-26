<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'otp',
                'label' => 'OTP Verification',
                'body' => '{code} is your {site_name} verification code. Valid for {expires_in} minutes. Do not share it with anyone.',
            ],
            [
                'key' => 'welcome',
                'label' => 'Welcome',
                'body' => 'Welcome to {site_name}, {name}! We are glad to have you with us. {site_url}',
            ],
            [
                'key' => 'registration',
                'label' => 'Registration',
                'body' => 'Hi {name}, your {site_name} account has been created successfully. {site_url}',
            ],
            [
                'key' => 'login',
                'label' => 'Login Verification',
                'body' => '{code} is your {site_name} login code. Do not share it with anyone.',
            ],
            [
                'key' => 'order_confirmation',
                'label' => 'Order Confirmation',
                'body' => 'Hi {name}, your {site_name} order #{order_id} has been confirmed. Total: {amount}.',
            ],
            [
                'key' => 'order_processing',
                'label' => 'Order Processing (auto)',
                'body' => 'Dear {customer_name}, your {site_name} order #{order_id} is being processed. Due: {due} Tk. Track: {website_tracking_url}',
            ],
            [
                'key' => 'invoice',
                'label' => 'Invoice',
                'body' => 'Hi {name}, your {site_name} invoice #{invoice_id} for {amount} is ready.',
            ],
            [
                'key' => 'password_reset',
                'label' => 'Password Reset',
                'body' => '{code} is your {site_name} password reset code. If you did not request this, please ignore.',
            ],
        ];

        foreach ($defaults as $template) {
            SmsTemplate::updateOrCreate(
                ['key' => $template['key']],
                ['label' => $template['label'], 'body' => $template['body'], 'is_active' => true]
            );
        }
    }
}
