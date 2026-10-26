<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Default SMS templates now carry {site_name} / {site_url} (global
     * placeholders, see SmsTemplate::globalPlaceholders) and the OTP text
     * uses {expires_in}. Only rows still holding the old default text are
     * updated — anything an admin edited is left alone. Missing defaults
     * are inserted so live sites match the seeder.
     */
    private const TEMPLATES = [
        'otp' => [
            'label' => 'OTP Verification',
            'old' => 'Your verification code is {code}. It will expire in 5 minutes.',
            'new' => '{code} is your {site_name} verification code. Valid for {expires_in} minutes. Do not share it with anyone.',
        ],
        'welcome' => [
            'label' => 'Welcome',
            'old' => 'Welcome to {app_name}, {name}! We are glad to have you with us.',
            'new' => 'Welcome to {site_name}, {name}! We are glad to have you with us. {site_url}',
        ],
        'registration' => [
            'label' => 'Registration',
            'old' => 'Hi {name}, your account has been created successfully.',
            'new' => 'Hi {name}, your {site_name} account has been created successfully. {site_url}',
        ],
        'login' => [
            'label' => 'Login Verification',
            'old' => 'Your login verification code is {code}.',
            'new' => '{code} is your {site_name} login code. Do not share it with anyone.',
        ],
        'order_confirmation' => [
            'label' => 'Order Confirmation',
            'old' => 'Hi {name}, your order #{order_id} has been confirmed. Total: {amount}.',
            'new' => 'Hi {name}, your {site_name} order #{order_id} has been confirmed. Total: {amount}.',
        ],
        'order_processing' => [
            'label' => 'Order Processing (auto)',
            'old' => 'Dear {customer_name}, your order #{order_id} is being processed. Due: {due} Tk. Track: {website_tracking_url}',
            'new' => 'Dear {customer_name}, your {site_name} order #{order_id} is being processed. Due: {due} Tk. Track: {website_tracking_url}',
        ],
        'invoice' => [
            'label' => 'Invoice',
            'old' => 'Hi {name}, your invoice #{invoice_id} for {amount} is ready.',
            'new' => 'Hi {name}, your {site_name} invoice #{invoice_id} for {amount} is ready.',
        ],
        'password_reset' => [
            'label' => 'Password Reset',
            'old' => 'Your password reset code is {code}. If you did not request this, please ignore.',
            'new' => '{code} is your {site_name} password reset code. If you did not request this, please ignore.',
        ],
    ];

    public function up(): void
    {
        foreach (self::TEMPLATES as $key => $template) {
            $row = DB::table('sms_templates')->where('key', $key)->first();

            if (! $row) {
                DB::table('sms_templates')->insert([
                    'key' => $key,
                    'label' => $template['label'],
                    'body' => $template['new'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (trim($row->body) === $template['old']) {
                DB::table('sms_templates')->where('id', $row->id)->update([
                    'body' => $template['new'],
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::TEMPLATES as $key => $template) {
            DB::table('sms_templates')
                ->where('key', $key)
                ->where('body', $template['new'])
                ->update(['body' => $template['old'], 'updated_at' => now()]);
        }
    }
};
