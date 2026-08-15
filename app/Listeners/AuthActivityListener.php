<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;

class AuthActivityListener
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('login')
            ->log('User logged in');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('logout')
            ->log('User logged out');
    }

    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->withProperties(['attempted_email' => $event->credentials['email'] ?? null])
            ->event('login.failed')
            ->log('Failed login attempt');
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('password.reset')
            ->log('Password was reset');
    }
}
