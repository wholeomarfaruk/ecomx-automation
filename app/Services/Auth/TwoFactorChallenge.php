<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;

/**
 * Puts a password-checked user into Fortify's two-factor challenge instead of
 * logging them in. Used by the Fortify login pipeline and by the storefront
 * auth modals, so no login path skips 2FA.
 */
class TwoFactorChallenge
{
    public function __construct(protected VerificationCodes $codes)
    {
    }

    public function required(User $user): bool
    {
        return $user->hasAnyTwoFactor();
    }

    /**
     * Same session keys Fortify's challenge reads (login.id / login.remember).
     * Without an authenticator app the first email/SMS code is sent right
     * away so the challenge screen already has one waiting.
     */
    public function start(Request $request, User $user, bool $remember): void
    {
        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $remember,
        ]);

        TwoFactorAuthenticationChallenged::dispatch($user);

        if ($user->hasAuthenticatorTwoFactor()) {
            return;
        }

        $channel = $user->twoFactorCodeChannels()[0];
        $request->session()->flash('two_factor_channel', $channel);

        if ($error = $this->codes->send($user, '2fa', $channel)) {
            $request->session()->flash('two_factor_send_error', $error);
        }
    }
}
