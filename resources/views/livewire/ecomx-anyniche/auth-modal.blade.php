@php
    $authLogoUrl = null;
    try {
        if ($logoId = \App\Models\Setting::get('site_logo_symbol')) {
            $authLogoUrl = file_path($logoId);
        }
    } catch (\Throwable $e) {
        $authLogoUrl = null;
    }
    $authLogoUrl ??= asset('logo/ecomx-square-logo.png');
@endphp
<div x-data="{ showPass: false }" x-show="$store.ui.authOpen" x-cloak class="jtc-modal-scrim" :class="$store.ui.authOpen && 'is-open'" @click.self="$store.ui.authOpen=false">
@guest
    <div class="jtc-modal">
        <div class="jtc-modal__head">
            <button class="jtc-modal__close" aria-label="Close" @click="$store.ui.authOpen=false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
            <span class="jtc-modal__logo"><img src="{{ $authLogoUrl }}" alt=""></span>
            <h3>{{ $mode === 'login' ? 'Welcome back' : ($mode === 'register' ? 'Create your account' : 'Reset password') }}</h3>
            <p>{{ $mode === 'login' ? 'Sign in to track orders and check out faster.' : ($mode === 'register' ? 'Join us to shop and track orders.' : 'We will help you get back into your account.') }}</p>
        </div>

        @if($mode === 'login')
            <div class="jtc-form">
                @if($formSuccess)
                    <p style="color:#1e7e34;font-size:12.5px;margin:0">{{ $formSuccess }}</p>
                @endif
                @if($formError && $formError !== 'gateway_unavailable')
                    <p class="jtc-form__error">{{ $formError }}</p>
                @endif

                @if($loginTab === 'password')
                    <form wire:submit.prevent="loginWithPassword" class="jtc-form" style="padding:0">
                        <label>Email or phone
                            <input wire:model="loginPhone" type="text" placeholder="you@example.com or phone number" required>
                            @error('loginPhone') <span class="jtc-form__error">{{ $message }}</span> @enderror
                        </label>
                        <label>
                            <div class="jtc-form__row">
                                <span>Password</span>
                                <a href="#" class="jtc-form__link" @click.prevent="$wire.switchMode('forgot')">Forgot password?</a>
                            </div>
                            <div style="position:relative">
                                <input wire:model="loginPassword" type="password" :type="showPass ? 'text' : 'password'" placeholder="••••••••" required>
                                <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:#1B7FC4" x-text="showPass ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('loginPassword') <span class="jtc-form__error">{{ $message }}</span> @enderror
                        </label>
                        <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">Sign in</button>
                        <p class="jtc-form__switch">
                            <a href="#" @click.prevent="$wire.switchLoginTab('otp')">Sign in with phone (OTP)</a>
                        </p>
                    </form>
                @else
                    @if(! $this->smsGatewayReady())
                        <div style="padding:14px;background:#f2f5f4;border-radius:8px">
                            <p style="font-size:13px;font-weight:600;margin:0 0 6px">Our SMS gateway is temporarily unavailable</p>
                            <p style="font-size:12.5px;margin:0 0 10px;color:#6b7a73">Please use password sign-in, or call us for help.</p>
                            <a href="tel:{{ config('ecomx-anyniche.phone') }}" class="jtc-btn jtc-btn--outline jtc-btn--block">Call {{ config('ecomx-anyniche.phone') }}</a>
                        </div>
                        <p class="jtc-form__switch">
                            <a href="#" @click.prevent="$wire.switchLoginTab('password')">Back to password sign in</a>
                        </p>
                    @elseif(! $otpSent)
                        <form wire:submit.prevent="sendLoginOtp" class="jtc-form" style="padding:0">
                            <label>Phone number
                                <input wire:model="otpPhone" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" required>
                                @error('otpPhone') <span class="jtc-form__error">{{ $message }}</span> @enderror
                            </label>
                            <p class="jtc-form__desc">We'll send a 6-digit code to your phone.</p>
                            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">Send OTP</button>
                            <p class="jtc-form__switch">
                                <a href="#" @click.prevent="$wire.switchLoginTab('password')">Back to password sign in</a>
                            </p>
                        </form>
                    @else
                        <form wire:submit.prevent="verifyLoginOtp" class="jtc-form" style="padding:0">
                            <p class="jtc-form__desc">Enter the code sent to {{ $otpPhone }}.</p>
                            <label>6-digit code
                                <input wire:model="otpCode" type="text" inputmode="numeric" maxlength="6" placeholder="••••••" autofocus required>
                                @error('otpCode') <span class="jtc-form__error">{{ $message }}</span> @enderror
                            </label>
                            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">Verify &amp; Sign in</button>
                        </form>
                        <p class="jtc-form__switch">
                            <button type="button" wire:click="sendLoginOtp" style="border:none;background:none;color:inherit;font:inherit;cursor:pointer">Resend code</button>
                            &nbsp;·&nbsp;
                            <a href="#" @click.prevent="$wire.switchLoginTab('password')">Back to password sign in</a>
                        </p>
                    @endif
                @endif

                <p class="jtc-form__switch">New here? <a href="#" @click.prevent="$wire.switchMode('register')">Create an account</a></p>
            </div>
        @elseif($mode === 'forgot')
            <div class="jtc-form">
                @if($formError && $formError !== 'gateway_unavailable')
                    <p class="jtc-form__error">{{ $formError }}</p>
                @endif

                @if($fpStep === 'select')
                    @if($formError === 'gateway_unavailable')
                        <div style="padding:14px;background:#f2f5f4;border-radius:8px">
                            <p style="font-size:13px;font-weight:600;margin:0 0 6px">We couldn't send a code right now</p>
                            <p style="font-size:12.5px;margin:0 0 10px;color:#6b7a73">Please try again shortly, or call us for help.</p>
                            <a href="tel:{{ config('ecomx-anyniche.phone') }}" class="jtc-btn jtc-btn--outline jtc-btn--block">Call {{ config('ecomx-anyniche.phone') }}</a>
                        </div>
                    @endif
                    <form wire:submit.prevent="sendForgotOtp" class="jtc-form" style="padding:0">
                        <p class="jtc-form__desc">Choose how you'd like to receive your verification code.</p>
                        <div style="display:flex;gap:8px">
                            <button type="button" class="jtc-btn jtc-form__channel-toggle {{ $fpChannel === 'phone' ? 'jtc-btn--primary' : 'jtc-btn--outline' }}" wire:click="switchForgotChannel('phone')">Phone number</button>
                            <button type="button" class="jtc-btn jtc-form__channel-toggle {{ $fpChannel === 'email' ? 'jtc-btn--primary' : 'jtc-btn--outline' }}" wire:click="switchForgotChannel('email')">Email</button>
                        </div>
                        @if($fpChannel === 'phone')
                            <label>Phone number
                                <input wire:model="fpIdentifier" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" required>
                                @error('fpIdentifier') <span class="jtc-form__error">{{ $message }}</span> @enderror
                            </label>
                        @else
                            <label>Email address
                                <input wire:model="fpIdentifier" type="email" placeholder="you@example.com" required>
                                @error('fpIdentifier') <span class="jtc-form__error">{{ $message }}</span> @enderror
                            </label>
                        @endif
                        <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">{{ $fpChannel === 'email' ? 'Send OTP to email' : 'Send OTP to phone' }}</button>
                    </form>
                    <p class="jtc-form__switch">Remembered it? <a href="#" @click.prevent="$wire.switchMode('login')">Back to sign in</a></p>
                @elseif($fpStep === 'otp')
                    <form wire:submit.prevent="verifyForgotOtp" class="jtc-form" style="padding:0">
                        <p class="jtc-form__desc">Enter the 6-digit code sent to {{ $fpIdentifier }}.</p>
                        <label>6-digit code
                            <input wire:model="fpCode" type="text" inputmode="numeric" maxlength="6" placeholder="••••••" autofocus required>
                            @error('fpCode') <span class="jtc-form__error">{{ $message }}</span> @enderror
                        </label>
                        <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">Verify code</button>
                    </form>
                    <p class="jtc-form__switch">
                        <button type="button" wire:click="sendForgotOtp" style="border:none;background:none;color:inherit;font:inherit;cursor:pointer">Resend code</button>
                        &nbsp;·&nbsp;
                        <button type="button" wire:click="backToForgotSelect" style="border:none;background:none;color:inherit;font:inherit;cursor:pointer">Change {{ $fpChannel === 'email' ? 'email' : 'number' }}</button>
                    </p>
                @else
                    <form wire:submit.prevent="resetPassword" class="jtc-form" style="padding:0">
                        <p class="jtc-form__desc">Create a new password for your account.</p>
                        <label>New password *
                            <div style="position:relative">
                                <input wire:model="fpNewPassword" type="password" :type="showPass ? 'text' : 'password'" required placeholder="Enter new password">
                                <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:#1B7FC4" x-text="showPass ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('fpNewPassword') <span class="jtc-form__error">{{ $message }}</span> @enderror
                        </label>
                        <label>Confirm new password *
                            <input wire:model="fpNewPassword_confirmation" type="password" :type="showPass ? 'text' : 'password'" required placeholder="Re-enter new password">
                        </label>
                        <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit">Reset password</button>
                    </form>
                @endif
            </div>
        @else
            <form wire:submit.prevent="register" class="jtc-form">
                @if($formError)
                    <p class="jtc-form__error">{{ $formError }}</p>
                @endif
                <label>Full name *
                    <input wire:model="registerName" type="text" required placeholder="Your name">
                    @error('registerName') <span class="jtc-form__error">{{ $message }}</span> @enderror
                </label>
                <label>Phone number *
                    <input wire:model="registerPhone" type="tel" inputmode="tel" required placeholder="01XXXXXXXXX">
                    @error('registerPhone') <span class="jtc-form__error">{{ $message }}</span> @enderror
                </label>
                <label>Email (optional)
                    <input wire:model="registerEmail" type="email" placeholder="you@example.com">
                    @error('registerEmail') <span class="jtc-form__error">{{ $message }}</span> @enderror
                </label>
                <label>Password *
                    <div style="position:relative">
                        <input wire:model="registerPassword" type="password" :type="showPass ? 'text' : 'password'" required placeholder="Create a password">
                        <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:#1B7FC4" x-text="showPass ? 'Hide' : 'Show'"></button>
                    </div>
                    @error('registerPassword') <span class="jtc-form__error">{{ $message }}</span> @enderror
                </label>
                <label class="jtc-form__check" style="align-items:flex-start">
                    <input type="checkbox" wire:model.live="agree" style="margin-top:2px">
                    <span>I accept the <a href="#" class="jtc-form__link">Terms &amp; Conditions</a> and <a href="#" class="jtc-form__link">Privacy Policy</a>.</span>
                </label>
                @error('agree') <span class="jtc-form__error">{{ $message }}</span> @enderror
                <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit" @if(! $agree) disabled @endif>Create account</button>
                <p class="jtc-form__switch">Have an account? <a href="#" @click.prevent="$wire.switchMode('login')">Sign in</a></p>
            </form>
        @endif
    </div>
@endguest
</div>
