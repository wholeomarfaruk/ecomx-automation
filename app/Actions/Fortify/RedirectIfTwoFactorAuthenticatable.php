<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Auth\TwoFactorChallenge;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;

/**
 * Fortify only challenges users with an authenticator app (TOTP). This also
 * challenges users who turned on email / SMS codes (see TwoFactorChallenge).
 */
class RedirectIfTwoFactorAuthenticatable extends FortifyRedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);
        $challenge = app(TwoFactorChallenge::class);

        if (!$user instanceof User || !$challenge->required($user)) {
            return $next($request);
        }

        $challenge->start($request, $user, $request->boolean('remember'));

        return $request->wantsJson()
            ? response()->json(['two_factor' => true])
            : redirect()->route('two-factor.login');
    }
}
