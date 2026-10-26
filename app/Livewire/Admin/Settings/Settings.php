<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Country;
use App\Services\Auth\VerificationCodes;
use App\Support\PhoneNumber;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Settings extends Component
{
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

    // Two-factor email / SMS codes
    public bool $two_factor_email = false;
    public bool $two_factor_sms = false;
    public $two_factor_password;

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
        $this->two_factor_email = (bool) $this->user->two_factor_email_enabled;
        $this->two_factor_sms = (bool) $this->user->two_factor_sms_enabled;

        // Keep the code box open across reloads while a code is still valid.
        $this->emailCodeSent = $this->codes()->hasPending($this->user, 'verify', 'email');
        $this->phoneCodeSent = $this->codes()->hasPending($this->user, 'verify', 'sms');
    }

    protected function codes(): VerificationCodes
    {
        return app(VerificationCodes::class);
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

        $this->reset('email_password', 'email_code', 'emailCodeSent');

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
        $this->reset('phone_password', 'phone_code', 'phoneCodeSent');

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

    // ── Contact verification ─────────────────────────────────────

    public function sendEmailCode()
    {
        $this->resetErrorBag('email_code');

        if ($this->user->email_verified_at) {
            return;
        }

        if ($error = $this->codes()->send($this->user, 'verify', 'email')) {
            $this->addError('email_code', $error);
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

        if ($error = $this->codes()->send($this->user, 'verify', 'sms')) {
            $this->addError('phone_code', $error);
            return;
        }

        $this->phoneCodeSent = true;
        $this->phoneSends++;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Verification code sent by SMS.']);
    }

    public function verifyEmailCode()
    {
        $this->validate(['email_code' => ['required', 'digits:6']], [], ['email_code' => 'code']);

        if ($error = $this->codes()->verify($this->user, 'verify', 'email', $this->email_code)) {
            $this->addError('email_code', $error);
            return;
        }

        $this->user->forceFill(['email_verified_at' => now()])->save();
        $this->reset('email_code', 'emailCodeSent');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Email verified!']);
    }

    public function verifyPhoneCode()
    {
        $this->validate(['phone_code' => ['required', 'digits:6']], [], ['phone_code' => 'code']);

        if ($error = $this->codes()->verify($this->user, 'verify', 'sms', $this->phone_code)) {
            $this->addError('phone_code', $error);
            return;
        }

        $this->user->forceFill(['phone_verified_at' => now()])->save();
        $this->reset('phone_code', 'phoneCodeSent');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Phone number verified!']);
    }

    /** Seconds until another verification code may be sent ('email' | 'sms'). */
    public function resendIn(string $channel): int
    {
        return $this->codes()->resendIn($this->user, 'verify', $channel);
    }

    // ── Two-factor: email / SMS codes ────────────────────────────

    public function updateTwoFactorMethods()
    {
        $this->validate(['two_factor_password' => 'required'], [], ['two_factor_password' => 'current password']);

        if ($this->two_factor_email && !$this->user->email_verified_at) {
            $this->addError('two_factor_email', 'Verify your email first.');
            return;
        }

        if ($this->two_factor_sms && !($this->user->phone && $this->user->phone_verified_at)) {
            $this->addError('two_factor_sms', 'Add and verify a phone number first.');
            return;
        }

        if (!$this->passwordMatches($this->two_factor_password, 'two_factor_password')) {
            return;
        }

        $this->user->forceFill([
            'two_factor_email_enabled' => $this->two_factor_email,
            'two_factor_sms_enabled' => $this->two_factor_sms,
        ])->save();

        $this->reset('two_factor_password');

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Two-factor methods updated.'
        ]);
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
