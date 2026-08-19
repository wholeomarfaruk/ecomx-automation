<?php

namespace App\Providers;

use App\Events\LoginDetected;
use App\Events\OtpRequested;
use App\Events\PasswordResetRequested;
use App\Events\UserRegistered;
use App\Listeners\AuthActivityListener;
use App\Listeners\NotificationEventListener;
use App\Models\ActivityLog;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, \App\Http\Responses\LoginResponse::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerActivityTracking();
        $this->registerAuthListeners();
        $this->registerNotificationListeners();
        $this->configureMailFromSettings();

        // ecomx-fashion theme's own components (<x-ux-img>, <x-product-card>, ...),
        // used bare (no namespace prefix) — matches how the theme's blade views call them.
        Blade::anonymousComponentPath(resource_path('views/ecomx-fashion/components'), null);

        // Admins must always be able to reach the panel to turn maintenance mode
        // back off — without this, enabling it via Site Settings locks everyone
        // (including admins) out with no way back in except the CLI. livewire/update
        // must be excluded too since every wire:click (including tab switches and
        // the maintenance toggle itself) is a background POST to that endpoint,
        // not to the admin/* page URL. login/two-factor-challenge are excluded so a
        // logged-out staff member can still sign in during maintenance in the first
        // place. '/' is excluded from this GLOBAL check entirely because it runs
        // before the web group's session middleware, so auth() is never available
        // here — the real "block anonymous, allow staff" decision for '/' happens
        // in App\Http\Middleware\PreventPublicMaintenanceForStaff (routes/web.php),
        // which runs later, after sessions/auth are resolved.
        PreventRequestsDuringMaintenance::except([
            'admin/*', 'livewire/*', 'login', 'two-factor-challenge', '/',
        ]);
    }

    protected function registerActivityTracking(): void
    {
        ActivityLog::creating(function (ActivityLog $activity) {
            if (app()->runningInConsole()) {
                return;
            }

            $activity->ip_address ??= request()->ip();
            $activity->user_agent ??= request()->userAgent();
        });
    }

    protected function registerAuthListeners(): void
    {
        $listener = AuthActivityListener::class;

        Event::listen(Login::class,         [$listener, 'handleLogin']);
        Event::listen(Logout::class,        [$listener, 'handleLogout']);
        Event::listen(Failed::class,        [$listener, 'handleFailed']);
        Event::listen(PasswordReset::class, [$listener, 'handlePasswordReset']);
    }

    protected function registerNotificationListeners(): void
    {
        $listener = NotificationEventListener::class;

        Event::listen(UserRegistered::class,        [$listener, 'handle']);
        Event::listen(PasswordResetRequested::class, [$listener, 'handle']);
        Event::listen(OtpRequested::class,          [$listener, 'handle']);
        Event::listen(LoginDetected::class,         [$listener, 'handle']);
    }

    // Migrations/seeders run before the settings table exists on a fresh
    // install (and some artisan commands run before any DB connection is
    // even configured), so this must never assume the table is there —
    // Email Configuration simply has no effect until it's been saved once.
    protected function configureMailFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        if (! Setting::get('enabled', true, 'email')) {
            return;
        }

        $mailer = Setting::get('active_mailer', null, 'email');

        if (! $mailer) {
            return;
        }

        config(['mail.default' => $mailer]);

        $credentials = Setting::get("{$mailer}_credentials", [], 'email');

        if ($mailer === 'smtp' && $credentials) {
            config([
                'mail.mailers.smtp.host' => $credentials['host'] ?? config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $credentials['port'] ?? config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $credentials['username'] ?? null,
                'mail.mailers.smtp.password' => $credentials['password'] ?? null,
                'mail.mailers.smtp.scheme' => $credentials['encryption'] ?? null,
            ]);
        } elseif ($credentials['api_key'] ?? null) {
            match ($mailer) {
                'resend' => config(['services.resend.key' => $credentials['api_key']]),
                'postmark' => config(['services.postmark.token' => $credentials['api_key']]),
                'ses' => null, // SES uses AWS credentials from services.ses, not a single api_key
                default => null,
            };
        }

        $fromEmail = Setting::get('from_email', null, 'email');
        $fromName = Setting::get('from_name', null, 'email');

        if ($fromEmail) {
            config([
                'mail.from.address' => $fromEmail,
                'mail.from.name' => $fromName ?? config('mail.from.name'),
            ]);
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
