<div x-data="{
        mode:'login', showPass:false, tab:'password', agree:false,
        fpStep:'select', fpChannel:'phone', fpIdentifier:'',
        fpOtp:['','','','','',''], showNewPass:false, showConfirmPass:false,
        resetAuthModal(){ this.mode='login'; this.fpStep='select'; this.fpChannel='phone'; this.fpIdentifier=''; this.fpOtp=['','','','','','']; },
        fpOtpInput(i,e){ let v=e.target.value.replace(/[^0-9]/g,'').slice(0,1); this.fpOtp[i]=v; if(v && e.target.nextElementSibling) e.target.nextElementSibling.focus(); },
        fpOtpBackspace(i,e){ if(e.key==='Backspace' && !this.fpOtp[i] && e.target.previousElementSibling) e.target.previousElementSibling.focus(); }
    }" x-show="$store.ui.authOpen" x-cloak class="modal" @click.self="$store.ui.authOpen=false">
    <div class="modal__box modal__box--md">
        <div class="modal__head">
            <p class="modal__title" x-text="mode==='login' ? 'Sign in' : mode==='register' ? 'Register' : 'Reset password'"></p>
            <button class="modal__close" @click="$store.ui.authOpen=false; resetAuthModal()" aria-label="Close">✕</button>
        </div>

        {{-- Sign-in mode toggle: password / OTP --}}
        <template x-if="mode==='login'">
            <div>
                <div style="display:flex;gap:8px;margin-bottom:18px">
                    <button class="rv-filter" :class="tab==='password' && 'is-active'" @click="tab='password'">Password</button>
                    <button class="rv-filter" :class="tab==='otp' && 'is-active'" @click="tab='otp'">OTP sign in</button>
                </div>
                <form @submit.prevent>
                    <div class="field"><label>Phone number</label><input inputmode="tel" placeholder="01XXXXXXXXX" required></div>
                    <template x-if="tab==='password'">
                        <div class="field">
                            <div style="display:flex;align-items:center;justify-content:space-between">
                                <label style="margin-bottom:0">Password</label>
                                <a href="#" @click.prevent="mode='forgot'; fpStep='select'" style="font-size:12px;color:var(--ac2);font-weight:600">Forgot password?</a>
                            </div>
                            <div style="position:relative">
                                <input :type="showPass ? 'text' : 'password'" placeholder="••••••••" required>
                                <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showPass ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>
                    </template>
                    <template x-if="tab==='otp'">
                        <p class="muted" style="font-size:12.5px;margin:0 0 16px">We'll send a 6-digit code to your phone.</p>
                    </template>
                    <button class="btn btn--primary btn--block" x-text="tab==='otp' ? 'Send OTP' : 'Sign in'"></button>
                </form>
                <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">New here? <a href="#" @click.prevent="mode='register'" style="color:var(--ac2);font-weight:600">Register</a></p>
            </div>
        </template>

        {{-- Register mode: name + phone + password (all required) --}}
        <template x-if="mode==='register'">
            <form @submit.prevent>
                <div class="field"><label>Full name *</label><input required placeholder="Your name"></div>
                <div class="field"><label>Phone number *</label><input inputmode="tel" required placeholder="01XXXXXXXXX"></div>
                <div class="field">
                    <label>Password *</label>
                    <div style="position:relative">
                        <input :type="showPass ? 'text' : 'password'" required placeholder="Create a password">
                        <button type="button" @click="showPass=!showPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showPass ? 'Hide' : 'Show'"></button>
                    </div>
                </div>
                <label style="display:flex;align-items:flex-start;gap:9px;font-size:12.5px;color:rgba(var(--pri-rgb),.7);margin-bottom:18px;cursor:pointer">
                    <input type="checkbox" x-model="agree" required style="margin-top:2px">
                    <span>I accept the <a href="#" style="color:var(--ac2)">Terms &amp; Conditions</a> and <a href="#" style="color:var(--ac2)">Privacy Policy</a>.</span>
                </label>
                <button class="btn btn--primary btn--block" :disabled="!agree" :style="!agree && 'opacity:.5;cursor:not-allowed'">Create account</button>
                <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">Have an account? <a href="#" @click.prevent="mode='login'" style="color:var(--ac2);font-weight:600">Sign in</a></p>
            </form>
        </template>

        {{-- Forgot password mode: select channel -> verify OTP -> set new password --}}
        <template x-if="mode==='forgot'">
            <div>
                {{-- Step 1: choose channel + enter identifier --}}
                <template x-if="fpStep==='select'">
                    <form @submit.prevent="fpStep='otp'">
                        <p class="muted" style="font-size:12.5px;margin:0 0 16px">Choose how you'd like to receive your verification code.</p>
                        <div style="display:flex;gap:8px;margin-bottom:18px">
                            <button type="button" class="rv-filter" :class="fpChannel==='phone' && 'is-active'" @click="fpChannel='phone'; fpIdentifier=''">Phone number</button>
                            <button type="button" class="rv-filter" :class="fpChannel==='email' && 'is-active'" @click="fpChannel='email'; fpIdentifier=''">Email</button>
                        </div>
                        <template x-if="fpChannel==='phone'">
                            <div class="field"><label>Phone number</label><input inputmode="tel" x-model="fpIdentifier" placeholder="01XXXXXXXXX" required></div>
                        </template>
                        <template x-if="fpChannel==='email'">
                            <div class="field"><label>Email address</label><input type="email" x-model="fpIdentifier" placeholder="you@example.com" required></div>
                        </template>
                        <button class="btn btn--primary btn--block" x-text="fpChannel==='email' ? 'Send OTP to email' : 'Send OTP to phone'"></button>
                        <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">Remembered it? <a href="#" @click.prevent="mode='login'" style="color:var(--ac2);font-weight:600">Back to sign in</a></p>
                    </form>
                </template>

                {{-- Step 2: verify OTP --}}
                <template x-if="fpStep==='otp'">
                    <form @submit.prevent="fpStep='reset'">
                        <p class="muted" style="font-size:12.5px;margin:0 0 16px">
                            Enter the 6-digit code sent to <strong style="color:var(--pri)" x-text="fpIdentifier || (fpChannel==='email' ? 'your email' : 'your phone')"></strong>.
                        </p>
                        <div style="display:flex;gap:8px;margin-bottom:18px">
                            <template x-for="(digit, i) in fpOtp" :key="i">
                                <input type="text" inputmode="numeric" maxlength="1" x-model="fpOtp[i]"
                                    @input="fpOtpInput(i, $event)" @keydown="fpOtpBackspace(i, $event)"
                                    style="width:100%;text-align:center;font-size:18px;font-weight:600;padding:13px 0;border:1px solid rgba(var(--pri-rgb),.15);border-radius:8px;background:var(--sec);color:var(--pri);outline:none"
                                    required>
                            </template>
                        </div>
                        <button class="btn btn--primary btn--block">Verify code</button>
                        <p class="muted" style="text-align:center;font-size:12.5px;margin:16px 0 0">
                            Didn't get it? <a href="#" @click.prevent style="color:var(--ac2);font-weight:600">Resend code</a>
                            &nbsp;·&nbsp;
                            <a href="#" @click.prevent="fpStep='select'" style="color:var(--ac2);font-weight:600">Change <span x-text="fpChannel==='email' ? 'email' : 'number'"></span></a>
                        </p>
                    </form>
                </template>

                {{-- Step 3: set new password --}}
                <template x-if="fpStep==='reset'">
                    <form @submit.prevent="mode='login'; fpStep='select'">
                        <p class="muted" style="font-size:12.5px;margin:0 0 16px">Create a new password for your account.</p>
                        <div class="field">
                            <label>New password *</label>
                            <div style="position:relative">
                                <input :type="showNewPass ? 'text' : 'password'" required placeholder="Enter new password">
                                <button type="button" @click="showNewPass=!showNewPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showNewPass ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>
                        <div class="field">
                            <label>Confirm new password *</label>
                            <div style="position:relative">
                                <input :type="showConfirmPass ? 'text' : 'password'" required placeholder="Re-enter new password">
                                <button type="button" @click="showConfirmPass=!showConfirmPass" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;font-size:12px;color:var(--ac2)" x-text="showConfirmPass ? 'Hide' : 'Show'"></button>
                            </div>
                        </div>
                        <button class="btn btn--primary btn--block">Reset password</button>
                    </form>
                </template>
            </div>
        </template>
    </div>
</div>
