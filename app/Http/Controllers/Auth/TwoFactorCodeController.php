<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\VerificationCodes;
use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;
use Illuminate\Validation\Rule;

/**
 * Email / SMS code half of the two-factor challenge. Mirrors Fortify's
 * TwoFactorAuthenticatedSessionController@store, but checks a one-time
 * code from VerificationCodes instead of the authenticator app.
 */
class TwoFactorCodeController extends Controller
{
    public function __construct(
        protected StatefulGuard $guard,
        protected VerificationCodes $codes,
    ) {
    }

    public function send(TwoFactorLoginRequest $request)
    {
        $user = $this->challengedUserOrRedirect($request);

        $channel = $request->validate([
            'channel' => ['required', Rule::in($user->twoFactorCodeChannels())],
        ])['channel'];

        if ($error = $this->codes->send($user, '2fa', $channel)) {
            return back()->with('two_factor_channel', $channel)->withErrors(['code' => $error]);
        }

        return back()
            ->with('two_factor_channel', $channel)
            ->with('status', 'A new code was sent to ' . VerificationCodes::mask($user, $channel) . '.');
    }

    public function verify(TwoFactorLoginRequest $request)
    {
        $user = $this->challengedUserOrRedirect($request);

        $data = $request->validate([
            'channel' => ['required', Rule::in($user->twoFactorCodeChannels())],
            'code' => ['required', 'string'],
        ]);

        if ($error = $this->codes->verify($user, '2fa', $data['channel'], $data['code'])) {
            event(new TwoFactorAuthenticationFailed($user));

            return back()->with('two_factor_channel', $data['channel'])->withErrors(['code' => $error]);
        }

        $request->session()->forget('login.id');

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        $this->guard->login($user, $request->remember());

        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class);
    }

    protected function challengedUserOrRedirect(TwoFactorLoginRequest $request)
    {
        if (!$request->hasChallengedUser()) {
            abort(redirect()->route('login'));
        }

        return $request->challengedUser();
    }
}
