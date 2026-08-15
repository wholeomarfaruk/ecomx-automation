<?php

namespace App\Support;

class PrebuiltEmailTemplates
{
    /**
     * Ready-made templates shipped by the developer/SaaS owner. Clients pick
     * one from the admin UI and activate it as-is — nothing is editable in
     * the client UI. Activating writes an EmailTemplate row keyed by
     * "template_key" (the functional slot it fills, e.g. "otp"), so
     * EmailTemplate::send('otp', ...) picks it up automatically. Add a new
     * one here (plus its Blade file under resources/views/emails/prebuilt/)
     * and it appears in the picker automatically — no other admin code
     * changes needed.
     *
     * @return array<int, array{key: string, template_key: string, label: string, description: string, subject: string, view: string}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'otp-classic',
                'template_key' => 'otp',
                'label' => 'OTP Verification — Classic',
                'description' => 'Centered card with a large, spaced-out OTP code.',
                'subject' => 'Your verification code',
                'view' => 'emails.prebuilt.otp-classic',
            ],
            [
                'key' => 'welcome-hero',
                'template_key' => 'welcome',
                'label' => 'Welcome — Hero Banner (Admin-created users)',
                'description' => 'Gradient hero header with a numbered getting-started list. Sent when an admin creates a user from the admin panel.',
                'subject' => 'Welcome to {app_name}, {name}!',
                'view' => 'emails.prebuilt.welcome-hero',
            ],
            [
                'key' => 'signup-welcome',
                'template_key' => 'signup_welcome',
                'label' => 'Signup Successful — Customer Welcome',
                'description' => 'Confirms account creation with a summary card, then welcomes the customer. Sent on public self-registration.',
                'subject' => "You're signed up! Welcome to {app_name}",
                'view' => 'emails.prebuilt.signup-welcome',
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        foreach (static::all() as $template) {
            if ($template['key'] === $key) {
                return $template;
            }
        }

        return null;
    }

    public static function renderBody(string $key): ?string
    {
        $template = static::find($key);

        if (! $template) {
            return null;
        }

        return view($template['view'])->render();
    }
}
