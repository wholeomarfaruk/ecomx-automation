<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Country;
use App\Models\EmailTemplate;
use App\Models\SmsGatewayConfig;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class Settings extends Component
{
    // Verification codes live in cache (not users.otp) so they never clash
    // with a storefront login/forgot-password OTP for the same account.
    public const CODE_TTL_MINUTES = 10;
    public const RESEND_COOLDOWN = 60;
    public const MAX_SENDS_PER_HOUR = 5;
    public const MAX_VERIFY_ATTEMPTS = 5;

    public $user;

    // Login email
    public $email;
    public $email_password;
    public $email_code;
    public bool $emailCodeSent = false;
    public int $emailSends = 0;

    // Phone
    public $country_code;
    public $phone;
    public $phone_password;
    public $phone_code;
    public bool $phoneCodeSent = false;
    public int $phoneSends = 0;

    // Password
    public $current_password;
    public $password;
    public $password_confirmation;

    public function mount()
    {
        $this->user = Auth::user();
        $this->email = $this->user->email;
        $this->country_code = $this->user->country_code;
        $this->phone = $this->user->phone;

        // Keep the code box open across reloads while a code is still valid.
        $this->emailCodeSent = $this->pendingCode('email') !== null;
        $this->phoneCodeSent = $this->pendingCode('phone') !== null;
    }

    protected function passwordMatches(?string $plain, string $errorKey): bool
    {
        if (blank($plain) || !Hash::check($plain, $this->user->password)) {
            $this->addError($errorKey, 'The current password is incorrect.');
            return false;
        }

        return true;
    }

    public function updateEmail()
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'email_password' => 'required',
        ], [], ['email_password' => 'current password']);

        $email = trim($this->email);

        if (strcasecmp($email, $this->user->email) === 0) {
            $this->addError('email', 'This is already your email.');
            return;
        }

        if (!$this->passwordMatches($this->email_password, 'email_password')) {
            return;
        }

        $this->user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $this->reset('email_password');
        $this->forgetCode('email');

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Email updated successfully!'
        ]);

        $this->sendEmailCode();
    }

    public function updatePhone()
    {
        $this->validate([
            'country_code' => ['nullable', 'required_with:phone', 'exists:countries,phone_code'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{6,20}$/'],
            'phone_password' => 'required',
        ], [
            'phone.regex' => 'Phone may only contain digits, spaces, dashes and a leading +.',
        ], ['phone_password' => 'current password']);

        [$countryCode, $phone] = $this->normalizedPhone();

        if ($phone === $this->user->phone && $countryCode === $this->user->country_code) {
            $this->addError('phone', 'This is already your phone number.');
            return;
        }

        if (!$this->passwordMatches($this->phone_password, 'phone_password')) {
            return;
        }

        $this->user->forceFill([
            'phone' => $phone,
            'country_code' => $countryCode,
            'phone_verified_at' => null,
        ])->save();

        $this->phone = $phone;
        $this->country_code = $countryCode;
        $this->reset('phone_password');
        $this->forgetCode('phone');

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $phone ? 'Phone number updated successfully!' : 'Phone number removed.'
        ]);

        if ($phone) {
            $this->sendPhoneCode();
        }
    }

    /**
     * Stored the same way the storefront stores phones (national number,
     * no trunk 0) so OTP login matches. An explicit "+..." / "00..." number
     * carries its own country code; otherwise the selected code is used.
     *
     * @return array{0: ?string, 1: ?string} [country_code, phone]
     */
    protected function normalizedPhone(): array
    {
        $raw = trim((string) $this->phone);

        if ($raw === '') {
            return [null, null];
        }

        $digits = preg_replace('/\D/', '', $raw);

        if (str_starts_with($raw, '+') || str_starts_with($digits, '00')) {
            $normalized = PhoneNumber::normalize($raw);
            return [$normalized['country_code'], $normalized['phone']];
        }

        return [$this->country_code, ltrim($digits, '0')];
    }

    // ── Verification ─────────────────────────────────────────────

    public function sendEmailCode()
    {
        $this->resetErrorBag('email_code');

        if ($this->user->email_verified_at) {
            return;
        }

        if (!$this->canSend('email', 'email_code')) {
            return;
        }

        $code = $this->issueCode('email', $this->user->email);

        try {
            $sent = EmailTemplate::send('otp', $this->user->email, [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'code' => $code,
                'app_name' => config('app.name'),
                'year' => now()->year,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Admin email verification send failed', ['user_id' => $this->user->id, 'error' => $e->getMessage()]);
            $sent = false;
        }

        if (!$sent) {
            $this->forgetCode('email');
            $this->addError('email_code', 'Could not send the email. Check Email Configuration and that the "otp" email template is active.');
            return;
        }

        $this->emailCodeSent = true;
        $this->emailSends++;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Verification code sent to ' . $this->user->email]);
    }

    public function sendPhoneCode()
    {
        $this->resetErrorBag('phone_code');

        if ($this->user->phone_verified_at || !$this->user->phone) {
            return;
        }

        $gateway = SmsGatewayConfig::active();
        if ($gateway === null || empty($gateway->credentials)) {
            $this->addError('phone_code', 'No active SMS gateway. Set one up in SMS Configuration first.');
            return;
        }

        if (!$this->canSend('phone', 'phone_code')) {
            return;
        }

        $code = $this->issueCode('phone', $this->user->country_code . $this->user->phone);

        try {
            $sent = Sms::sendOTP(PhoneNumber::local($this->user->phone), $code)->success;
        } catch (\Throwable $e) {
            Log::warning('Admin phone verification send failed', ['user_id' => $this->user->id, 'error' => $e->getMessage()]);
            $sent = false;
        }

        if (!$sent) {
            $this->forgetCode('phone');
            $this->addError('phone_code', 'Could not send the SMS. Check the SMS gateway and try again.');
            return;
        }

        $this->phoneCodeSent = true;
        $this->phoneSends++;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Verification code sent by SMS.']);
    }

    public function verifyEmailCode()
    {
        if ($this->checkCode('email', $this->user->email, 'email_code')) {
            $this->user->forceFill(['email_verified_at' => now()])->save();
            $this->reset('email_code', 'emailCodeSent');
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Email verified!']);
        }
    }

    public function verifyPhoneCode()
    {
        if ($this->checkCode('phone', $this->user->country_code . $this->user->phone, 'phone_code')) {
            $this->user->forceFill(['phone_verified_at' => now()])->save();
            $this->reset('phone_code', 'phoneCodeSent');
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Phone number verified!']);
        }
    }

    /** Seconds until another code may be sent on this channel (drives the resend countdown). */
    public function resendIn(string $channel): int
    {
        return RateLimiter::availableIn($this->throttleKey($channel, 'cooldown'));
    }

    protected function canSend(string $channel, string $errorKey): bool
    {
        $cooldownKey = $this->throttleKey($channel, 'cooldown');
        $hourlyKey = $this->throttleKey($channel, 'hourly');

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $this->addError($errorKey, 'Please wait ' . RateLimiter::availableIn($cooldownKey) . ' seconds before requesting another code.');
            return false;
        }

        if (RateLimiter::tooManyAttempts($hourlyKey, self::MAX_SENDS_PER_HOUR)) {
            $this->addError($errorKey, 'Too many codes requested. Try again in ' . ceil(RateLimiter::availableIn($hourlyKey) / 60) . ' minutes.');
            return false;
        }

        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN);
        RateLimiter::hit($hourlyKey, 3600);

        return true;
    }

    protected function issueCode(string $channel, string $target): string
    {
        $code = (string) random_int(100000, 999999);

        // Bound to the target so a code sent to an old address can't verify a new one.
        Cache::put($this->codeKey($channel), [
            'hash' => hash('sha256', $code),
            'target' => $target,
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_TTL_MINUTES));

        return $code;
    }

    protected function checkCode(string $channel, string $target, string $errorKey): bool
    {
        $this->validate([$errorKey => ['required', 'digits:6']], [], [$errorKey => 'code']);

        $pending = $this->pendingCode($channel);

        if (!$pending || $pending['target'] !== $target) {
            $this->addError($errorKey, 'This code has expired. Send a new one.');
            return false;
        }

        if ($pending['attempts'] >= self::MAX_VERIFY_ATTEMPTS) {
            $this->forgetCode($channel);
            $this->addError($errorKey, 'Too many wrong attempts. Send a new code.');
            return false;
        }

        if (!hash_equals($pending['hash'], hash('sha256', trim($this->$errorKey)))) {
            $pending['attempts']++;
            Cache::put($this->codeKey($channel), $pending, now()->addMinutes(self::CODE_TTL_MINUTES));
            $this->addError($errorKey, 'Incorrect code. ' . (self::MAX_VERIFY_ATTEMPTS - $pending['attempts']) . ' attempts left.');
            return false;
        }

        $this->forgetCode($channel);

        return true;
    }

    protected function pendingCode(string $channel): ?array
    {
        return Cache::get($this->codeKey($channel));
    }

    protected function forgetCode(string $channel): void
    {
        Cache::forget($this->codeKey($channel));
        $this->{$channel . 'CodeSent'} = false;
    }

    protected function codeKey(string $channel): string
    {
        return 'admin-verify:' . $this->user->id . ':' . $channel;
    }

    protected function throttleKey(string $channel, string $kind): string
    {
        return 'admin-verify-' . $kind . ':' . $this->user->id . ':' . $channel;
    }

    // ── Password ─────────────────────────────────────────────────

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, $this->user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->password),
            'password_set_at' => now(),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Password updated successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.admin.settings.settings', [
            'countries' => Country::active()->orderBy('name')->get(['name', 'phone_code'])->unique('phone_code')->values(),
        ])->layout('layouts.admin.admin');
    }
}
