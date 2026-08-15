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
                'body' => 'Your verification code is {code}. It will expire in 5 minutes.',
            ],
            [
                'key' => 'welcome',
                'label' => 'Welcome',
                'body' => 'Welcome to {app_name}, {name}! We are glad to have you with us.',
            ],
            [
                'key' => 'registration',
                'label' => 'Registration',
                'body' => 'Hi {name}, your account has been created successfully.',
            ],
            [
                'key' => 'login',
                'label' => 'Login Verification',
                'body' => 'Your login verification code is {code}.',
            ],
            [
                'key' => 'order_confirmation',
                'label' => 'Order Confirmation',
                'body' => 'Hi {name}, your order #{order_id} has been confirmed. Total: {amount}.',
            ],
            [
                'key' => 'invoice',
                'label' => 'Invoice',
                'body' => 'Hi {name}, your invoice #{invoice_id} for {amount} is ready.',
            ],
            [
                'key' => 'password_reset',
                'label' => 'Password Reset',
                'body' => 'Your password reset code is {code}. If you did not request this, please ignore.',
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
