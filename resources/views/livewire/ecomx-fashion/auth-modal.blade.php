<div x-data="{ showPass: false }" x-show="$store.ui.authOpen" x-cloak class="modal" @click.self="$store.ui.authOpen=false" x-on:authenticated.window="$store.ui.authOpen=false">
@guest
    <div class="modal__box modal__box--md">
        <div class="modal__head">
            <p class="modal__title">{{ $mode === 'login' ? 'Sign in' : 'Register' }}</p>
            <button class="modal__close" @click="$store.ui.authOpen=false" aria-label="Close">✕</button>
        </div>

        @if($mode === 'login')
            <div>
                <div style="display:flex;gap:8px;margin-bottom:18px">
                    <button type="button" class="rv-filter {{ $loginTab === 'password' ? 'is-active' : '' }}" wire:click="switchLoginTab('password')">Password</button>
                    <button type="button" class="rv-filter {{ $loginTab === 'otp' ? 'is-active' : '' }}" wire:click="switchLoginTab('otp')">OTP sign in</button>
                </div>

                @if($formError && $formError !== 'gateway_unavailable')
                    <p style="color:#c0392b;font-size:12.5px;margin-bottom:14px">{{ $formError }}</p>
                @endif

                @if($loginTab === 'password')
                    <form wire:submit.prevent="loginWithPassword">
                        <div class="field">
                            <label>Phone number</label>
                            <input wire:model="loginPhone" inputmode="tel" placeholder="01XXXXXXXXX" required>
                            @error('loginPhone') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label>Password</label>
                            <div style="position:relative">
                                <input wire:model="loginPassword" :type="showPass ? 'text' : 'password'" placeholder="••••••••" required>
                                <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showPass ? 'Hide' : 'Show'"></button>
                            </div>
                            @error('loginPassword') <span class="field__error">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="btn btn--primary btn--block">Sign in</button>
                    </form>
                @else
                    @if(! $this->smsGatewayReady())
                        <div style="padding:14px;background:rgba(var(--pri-rgb),.03);border-radius:10px;margin-bottom:10px">
                            <p style="font-size:13px;font-weight:600;margin:0 0 6px">Our SMS gateway is temporarily unavailable</p>
                            <p class="muted" style="font-size:12.5px;margin:0 0 10px">Please use password sign-in, or call us for help.</p>
                            <a href="tel:{{ config('ecomx-fashion.phone') }}" class="btn btn--outline btn--pill btn--block">Call {{ config('ecomx-fashion.phone') }}</a>
                        </div>
                    @elseif(! $otpSent)
                        <form wire:submit.prevent="sendLoginOtp">
                            <div class="field">
                                <label>Phone number</label>
                                <input wire:model="otpPhone" inputmode="tel" placeholder="01XXXXXXXXX" required>
                                @error('otpPhone') <span class="field__error">{{ $message }}</span> @enderror
                            </div>
                            <p class="muted" style="font-size:12.5px;margin:0 0 16px">We'll send a 6-digit code to your phone.</p>
                            <button type="submit" class="btn btn--primary btn--block">Send OTP</button>
                        </form>
                    @else
                        <form wire:submit.prevent="verifyLoginOtp">
                            <p class="muted" style="font-size:12.5px;margin-bottom:14px">Enter the code sent to {{ $otpPhone }}.</p>
                            <div class="field">
                                <label>6-digit code</label>
                                <input wire:model="otpCode" inputmode="numeric" maxlength="6" placeholder="••••••" autofocus required>
                                @error('otpCode') <span class="field__error">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="btn btn--primary btn--block" style="margin-bottom:10px">Verify &amp; Sign in</button>
                        </form>
                        <button type="button" wire:click="sendLoginOtp" style="border:none;background:none;font-size:12px;color:var(--ac2);font-weight:600;cursor:pointer;display:block;width:100%;text-align:center">Resend code</button>
                    @endif
                @endif

                <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">New here? <a href="#" @click.prevent="$wire.switchMode('register')" style="color:var(--ac2);font-weight:600">Register</a></p>
            </div>
        @else
            <form wire:submit.prevent="register">
                @if($formError)
                    <p style="color:#c0392b;font-size:12.5px;margin-bottom:14px">{{ $formError }}</p>
                @endif
                <div class="field">
                    <label>Full name *</label>
                    <input wire:model="registerName" required placeholder="Your name">
                    @error('registerName') <span class="field__error">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Phone number *</label>
                    <input wire:model="registerPhone" inputmode="tel" required placeholder="01XXXXXXXXX">
                    @error('registerPhone') <span class="field__error">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Password *</label>
                    <div style="position:relative">
                        <input wire:model="registerPassword" :type="showPass ? 'text' : 'password'" required placeholder="Create a password">
                        <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showPass ? 'Hide' : 'Show'"></button>
                    </div>
                    @error('registerPassword') <span class="field__error">{{ $message }}</span> @enderror
                </div>
                <label style="display:flex;align-items:flex-start;gap:9px;font-size:12.5px;color:rgba(var(--pri-rgb),.7);margin-bottom:18px;cursor:pointer">
                    <input type="checkbox" wire:model.live="agree" style="margin-top:2px">
                    <span>I accept the <a href="#" style="color:var(--ac2)">Terms &amp; Conditions</a> and <a href="#" style="color:var(--ac2)">Privacy Policy</a>.</span>
                </label>
                @error('agree') <span class="field__error" style="display:block;margin-top:-12px;margin-bottom:14px">{{ $message }}</span> @enderror
                <button type="submit" class="btn btn--primary btn--block" @if(! $agree) disabled style="opacity:.5;cursor:not-allowed" @endif>Create account</button>
                <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">Have an account? <a href="#" @click.prevent="$wire.switchMode('login')" style="color:var(--ac2);font-weight:600">Sign in</a></p>
            </form>
        @endif
    </div>
@endguest
</div>
