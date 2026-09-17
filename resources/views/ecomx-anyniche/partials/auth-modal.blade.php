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
<div x-data="{
        authMode: 'login',
        authSuccess: false,
        authOtpSent: false,
        authLoading: false,
        authError: '',
        authForm: { name:'', email:'', phone:'', login:'', password:'', passwordConfirmation:'', remember:false },
        authOtpForm: { phone:'', code:'' },
        get authTitle() { return this.authMode === 'login' ? 'Sign in' : this.authMode === 'signup' ? 'Create account' : 'Sign in with OTP'; },
        get authSubtitle() { return this.authMode === 'login' ? 'Welcome back — sign in to continue.' : this.authMode === 'signup' ? 'Join us for faster checkout.' : 'We will text you a verification code.'; },
        get authSubmitText() { return this.authMode === 'signup' ? 'Create account' : 'Sign in'; },
        get authSwitchPrompt() { return this.authMode === 'login' ? 'New here?' : 'Already have an account?'; },
        get authSwitchAction() { return this.authMode === 'login' ? 'Create an account' : 'Sign in'; },
        toggleAuthMode() { this.authMode = this.authMode === 'login' ? 'signup' : 'login'; this.authError = ''; },
        switchToOtpMode() { this.authMode = 'otp'; this.authError = ''; },
        submitAuth() {
            this.authLoading = true; this.authError = '';
            this.$nextTick(() => { this.authLoading = false; });
        },
        sendAuthOtp() { this.authOtpSent = true; },
        verifyAuthOtp() { this.authSuccess = true; },
        resetAuthModal() { this.authMode = 'login'; this.authSuccess = false; this.authOtpSent = false; this.authError = ''; },
    }" x-show="$store.ui.authOpen" x-cloak class="jtc-modal-scrim" :class="$store.ui.authOpen && 'is-open'" @click="$store.ui.authOpen = false; resetAuthModal()">
    <div class="jtc-modal" @click.stop>
        <div class="jtc-modal__head">
            <button class="jtc-modal__close" aria-label="Close" @click="$store.ui.authOpen = false; resetAuthModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
            <span class="jtc-modal__logo"><img src="{{ $authLogoUrl }}" alt=""></span>
            <template x-if="!authSuccess">
                <div>
                    <h3 x-text="authTitle"></h3>
                    <p x-text="authSubtitle"></p>
                </div>
            </template>
            <template x-if="authSuccess">
                <div>
                    <h3>You're in</h3>
                    <p>Signed in successfully.</p>
                </div>
            </template>
        </div>

        <div class="jtc-form" x-show="authSuccess" x-cloak>
            <template x-if="authMode === 'login' || authMode === 'otp'">
                <button type="button" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit"
                        @click="$store.ui.authOpen = false; authSuccess = false">Continue shopping</button>
            </template>
            <template x-if="authMode === 'signup'">
                <button type="button" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit"
                        @click="authSuccess = false; authMode = 'login'">Login</button>
            </template>
        </div>

        <form class="jtc-form" @submit.prevent="submitAuth()" x-show="!authSuccess && authMode !== 'otp'">
            <p class="jtc-form__error" x-show="authError" x-cloak x-text="authError"></p>

            <label x-show="authMode === 'signup'" x-cloak>Full name
                <input type="text" x-model="authForm.name">
            </label>

            <label x-show="authMode === 'signup'" x-cloak>Email
                <input type="email" placeholder="you@example.com" x-model="authForm.email">
            </label>
            <label x-show="authMode === 'signup'" x-cloak>Phone
                <input type="tel" placeholder="01XXXXXXXXX" x-model="authForm.phone">
            </label>
            <label x-show="authMode === 'login'">Email or phone
                <input type="text" placeholder="you@example.com or phone number" x-model="authForm.login">
            </label>

            <label>Password
                <input type="password" placeholder="••••••••" x-model="authForm.password">
            </label>
            <label x-show="authMode === 'signup'" x-cloak>Confirm password
                <input type="password" placeholder="••••••••" x-model="authForm.passwordConfirmation">
            </label>

            <div class="jtc-form__row" x-show="authMode === 'login'">
                <label class="jtc-form__check"><input type="checkbox" x-model="authForm.remember">Remember me</label>
                <a href="#" class="jtc-form__link" @click.prevent>Forgot password?</a>
            </div>

            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit"
                    :disabled="authLoading" x-text="authLoading ? 'Please wait…' : authSubmitText"></button>

            <p class="jtc-form__switch" x-show="authMode === 'login'" x-cloak>
                <a href="#" @click.prevent="switchToOtpMode()">Sign in with phone (OTP)</a>
            </p>

            <p class="jtc-form__switch">
                <span x-text="authSwitchPrompt"></span>
                <a href="#" @click.prevent="toggleAuthMode()" x-text="authSwitchAction"></a>
            </p>
        </form>

        <form class="jtc-form" @submit.prevent="authOtpSent ? verifyAuthOtp() : sendAuthOtp()" x-show="!authSuccess && authMode === 'otp'" x-cloak>
            <p class="jtc-form__error" x-show="authError" x-cloak x-text="authError"></p>

            <label x-show="!authOtpSent">Phone
                <input type="tel" placeholder="01XXXXXXXXX" x-model="authOtpForm.phone">
            </label>

            <template x-if="authOtpSent">
                <div>
                    <p class="jtc-form__desc">Code sent to <strong x-text="authOtpForm.phone"></strong></p>
                    <label>Verification code
                        <input type="text" inputmode="numeric" maxlength="6" placeholder="••••••" x-model="authOtpForm.code">
                    </label>
                </div>
            </template>

            <button type="submit" class="jtc-btn jtc-btn--primary jtc-btn--block jtc-form__submit"
                    :disabled="authLoading"
                    x-text="authLoading ? 'Please wait…' : (authOtpSent ? 'Verify & sign in' : 'Send code')"></button>

            <p class="jtc-form__switch" x-show="authOtpSent" x-cloak>
                <a href="#" @click.prevent="authOtpSent = false; authOtpForm.code = ''">Use a different number</a>
            </p>

            <p class="jtc-form__switch">
                <a href="#" @click.prevent="authMode = 'login'; authError = ''">Back to password sign in</a>
            </p>
        </form>
    </div>
</div>
