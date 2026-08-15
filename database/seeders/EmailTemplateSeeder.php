<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'otp',
                'label' => 'OTP Verification',
                'subject' => 'Your verification code',
                'body' => 'Hi {name},<br><br>Your verification code is <strong>{code}</strong>. It will expire in 5 minutes.',
            ],
            [
                'key' => 'welcome',
                'label' => 'Welcome',
                'subject' => 'Welcome to {app_name}',
                'body' => 'Hi {name},<br><br>Welcome to {app_name}! We are glad to have you with us.',
            ],
            [
                'key' => 'signup_welcome',
                'label' => 'Signup Welcome',
                'subject' => "You're signed up! Welcome to {app_name}",
                'body' => 'Hi {name},<br><br>Your {app_name} account ({email}) has been created successfully. Welcome aboard!',
            ],
            [
                'key' => 'registration',
                'label' => 'Registration',
                'subject' => 'Your account has been created',
                'body' => 'Hi {name},<br><br>Your account has been created successfully.',
            ],
            [
                'key' => 'login',
                'label' => 'Login Verification',
                'subject' => 'Your login verification code',
                'body' => 'Hi {name},<br><br>Your login verification code is <strong>{code}</strong>.',
            ],
            [
                'key' => 'order_confirmation',
                'label' => 'Order Confirmation',
                'subject' => 'Your order #{order_id} is confirmed',
                'body' => 'Hi {name},<br><br>Your order #{order_id} has been confirmed. Total: {amount}.',
            ],
            [
                'key' => 'invoice',
                'label' => 'Invoice',
                'subject' => 'Your invoice #{invoice_id}',
                'body' => 'Hi {name},<br><br>Your invoice #{invoice_id} for {amount} is ready.',
            ],
            [
                'key' => 'password_reset',
                'label' => 'Password Reset',
                'subject' => 'Reset your password',
                'body' => 'Hi {name},<br><br>Your password reset code is <strong>{code}</strong>. If you did not request this, please ignore this email.',
            ],
        ];

        foreach ($defaults as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'label' => $template['label'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'is_active' => true,
                ]
            );
        }
    }
}
