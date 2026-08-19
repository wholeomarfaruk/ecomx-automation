<div x-data="{ mode:'login', showPass:false, tab:'password', agree:false }" x-show="$store.ui.authOpen" x-cloak class="modal" @click.self="$store.ui.authOpen=false">
    <div class="modal__box modal__box--md">
        <div class="modal__head">
            <p class="modal__title" x-text="mode==='login' ? 'Sign in' : 'Register'"></p>
            <button class="modal__close" @click="$store.ui.authOpen=false" aria-label="Close">✕</button>
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
                            <label>Password</label>
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
    </div>
</div>
