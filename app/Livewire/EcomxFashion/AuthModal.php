<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\User\Status;
use App\Models\Customer;
use App\Models\SmsGatewayConfig;
use App\Models\User;
use App\Sms\Facades\Sms;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Storefront sign-in/register modal (@livewire'd into the layout, opened
 * via the $store.ui.authOpen Alpine flag — see partials/header.blade.php
 * and this component's own view). Three modes: password login, OTP login,
 * register — all against the same `users`/`customers` tables the storefront
 * Checkout flow already creates accounts in, so a phone number recognized
 * from a past order works here too.
 */
class AuthModal extends Component
{
    /** login | register */
    public string $mode = 'login';

    /** password | otp */
    public string $loginTab = 'password';

    // Password login
    public string $loginPhone = '';
    public string $loginPassword = '';

    // OTP login
    public string $otpPhone = '';
    public string $otpCode = '';
    public bool $otpSent = false;

    // Register
    public string $registerName = '';
    public string $registerPhone = '';
    public string $registerPassword = '';
    public bool $agree = false;

    public string $formError = '';

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->resetForms();
    }

    public function switchLoginTab(string $tab): void
    {
        $this->loginTab = $tab;
        $this->formError = '';
        $this->otpSent = false;
        $this->otpCode = '';
    }

    private function resetForms(): void
    {
        $this->reset([
            'loginPhone', 'loginPassword',
            'otpPhone', 'otpCode', 'otpSent',
            'registerName', 'registerPhone', 'registerPassword', 'agree',
            'formError',
        ]);
    }

    /** A usable SMS gateway needs an active row with credentials actually filled in, not just is_active=true on an empty row. */
    public function smsGatewayReady(): bool
    {
        $active = SmsGatewayConfig::active();

        return $active !== null && ! empty($active->credentials);
    }

    private function throttleKey(string $action, string $identifier): string
    {
        return 'auth:' . $action . ':' . Str::lower($identifier) . ':' . request()->ip();
    }

    public function loginWithPassword(): void
    {
        $this->formError = '';

        $this->validate([
            'loginPhone' => 'required|string|max:20',
            'loginPassword' => 'required|string',
        ]);

        $key = $this->throttleKey('password', $this->loginPhone);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->formError = "Too many attempts. Please try again in {$seconds} seconds.";

            return;
        }

        $user = User::where('phone', $this->loginPhone)->first();

        if (! $user || ! Hash::check($this->loginPassword, $user->password)) {
            RateLimiter::hit($key, 60);
            $this->formError = 'Incorrect phone number or password.';

            return;
        }

        if (! $user->status->isActive()) {
            $this->formError = 'This account is not active. Please contact support.';

            return;
        }

        RateLimiter::clear($key);
        auth()->login($user, remember: true);

        $this->resetForms();
        $this->dispatch('authenticated');
    }

    public function sendLoginOtp(): void
    {
        $this->formError = '';

        $this->validate(['otpPhone' => 'required|string|max:20']);

        if (! $this->smsGatewayReady()) {
            $this->formError = 'gateway_unavailable';

            return;
        }

        $key = $this->throttleKey('otp-send', $this->otpPhone);

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->formError = "Too many code requests. Please try again in {$seconds} seconds.";

            return;
        }

        $user = User::where('phone', $this->otpPhone)->first();

        if (! $user) {
            RateLimiter::hit($key, 300);
            // Same message whether or not the phone has an account — don't
            // let this form be used to enumerate which phone numbers are registered.
            $this->otpSent = true;

            return;
        }

        if (! $user->status->isActive()) {
            $this->formError = 'This account is not active. Please contact support.';

            return;
        }

        RateLimiter::hit($key, 300);

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp' => $code,
            'otp_expires_at' => now()->addMinutes(5),
        ])->save();

        $response = Sms::sendOTP($user->phone, $code);

        if (! $response->success) {
            $this->formError = 'gateway_unavailable';

            return;
        }

        $this->otpSent = true;
    }

    public function verifyLoginOtp(): void
    {
        $this->formError = '';

        $this->validate(['otpCode' => 'required|string|max:6']);

        $key = $this->throttleKey('otp-verify', $this->otpPhone);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->formError = "Too many attempts. Please try again in {$seconds} seconds.";

            return;
        }

        $user = User::where('phone', $this->otpPhone)->first();

        if (! $user || ! $user->otp || $user->otp !== trim($this->otpCode)) {
            RateLimiter::hit($key, 60);
            $this->formError = 'The code you entered is incorrect.';

            return;
        }

        if (! $user->otp_expires_at || Carbon::parse($user->otp_expires_at)->isPast()) {
            $this->formError = 'This code has expired — please request a new one.';

            return;
        }

        RateLimiter::clear($key);

        $user->forceFill(['otp' => null, 'otp_expires_at' => null])->save();
        auth()->login($user, remember: true);

        $this->resetForms();
        $this->dispatch('authenticated');
    }

    public function register(): void
    {
        $this->formError = '';

        $this->validate([
            'registerName' => 'required|string|max:150',
            'registerPhone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'registerPassword' => 'required|string|min:8',
            'agree' => 'accepted',
        ], [
            'registerPhone.unique' => 'An account with this phone number already exists.',
            'agree.accepted' => 'You must accept the terms to continue.',
        ]);

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'seldomfashion.local';

        $user = User::create([
            'name' => $this->registerName,
            'email' => 'user+' . Str::random(10) . '@' . $host,
            'password' => $this->registerPassword,
            'phone' => $this->registerPhone,
            'status' => Status::ACTIVE,
        ]);

        [$firstName, $lastName] = array_pad(explode(' ', trim($this->registerName), 2), 2, null);
        $code = 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);

        Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->registerName,
            'phone' => $this->registerPhone,
            'status' => 'active',
        ]);

        auth()->login($user, remember: true);

        $this->resetForms();
        $this->dispatch('authenticated');
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.auth-modal');
    }
}
