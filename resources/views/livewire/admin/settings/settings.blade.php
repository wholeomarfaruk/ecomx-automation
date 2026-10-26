{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'Account Settings', slug: 'settings' }">
    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh] p-6">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="max-w-4xl mx-auto space-y-6">
            {{-- Account Status --}}
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Status</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Email Verification</span>
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Unverified
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Phone Verification</span>
                        @if($user->phone_verified_at)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Verified
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Not Verified
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Role</span>
                        @if($user->roles->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                No Role
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Panels</span>
                        @if($user->panels->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->panels as $panel)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $panel->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                No Panels
                            </span>
                        @endif
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Member Since</span>
                        <span class="text-sm text-gray-900">{{ local_time($user->created_at)?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Last Updated</span>
                        <span class="text-sm text-gray-900">{{ local_time($user->updated_at)?->format('M d, Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>
            {{-- Security Settings --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900">Security Settings</h2>
                <p class="text-sm text-gray-500 mb-6">Every change here needs your current password.</p>

                <div class="divide-y divide-gray-200">

                    {{-- Login Email --}}
                    <div class="pb-6 space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900">Login Email</h3>
                                <p class="text-sm text-gray-500 break-all">Current: {{ $user->email }}</p>
                            </div>
                            @if($user->email_verified_at)
                                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                            @else
                                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unverified</span>
                            @endif
                        </div>

                        @unless($user->email_verified_at)
                            @include('livewire.admin.settings.partials.verify-box', [
                                'channel' => 'email',
                                'sent' => $emailCodeSent,
                                'sends' => $emailSends,
                                'resendIn' => $this->resendIn('email'),
                                'destination' => $user->email,
                                'sendAction' => 'sendEmailCode',
                                'verifyAction' => 'verifyEmailCode',
                                'codeField' => 'email_code',
                            ])
                        @endunless

                        <form wire:submit.prevent="updateEmail" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">New Email</label>
                                    <input type="email" wire:model="email" id="email" autocomplete="email"
                                        class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="email_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" wire:model="email_password" id="email_password" autocomplete="current-password"
                                        class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('email_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-xs text-gray-500">You will log in with the new email. A verification code is sent to it.</p>
                                <button type="submit" wire:loading.attr="disabled" wire:target="updateEmail"
                                    class="shrink-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="updateEmail">Change Email</span>
                                    <span wire:loading wire:target="updateEmail">Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Phone Number --}}
                    <div class="py-6 space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900">Phone Number</h3>
                                <p class="text-sm text-gray-500">Current: {{ $user->phone ? trim($user->country_code . ' ' . $user->phone) : 'Not set' }}</p>
                            </div>
                            @if($user->phone)
                                @if($user->phone_verified_at)
                                    <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                                @else
                                    <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Not Verified</span>
                                @endif
                            @endif
                        </div>

                        @if($user->phone && !$user->phone_verified_at)
                            @include('livewire.admin.settings.partials.verify-box', [
                                'channel' => 'phone',
                                'sent' => $phoneCodeSent,
                                'sends' => $phoneSends,
                                'resendIn' => $this->resendIn('sms'),
                                'destination' => trim($user->country_code . ' ' . $user->phone),
                                'sendAction' => 'sendPhoneCode',
                                'verifyAction' => 'verifyPhoneCode',
                                'codeField' => 'phone_code',
                            ])
                        @endif

                        <form wire:submit.prevent="updatePhone" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">New Phone</label>
                                    <div class="mt-1 flex gap-1.5">
                                        <select wire:model="country_code" id="country_code"
                                            class="w-24 shrink-0 p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Code</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->phone_code }}">{{ $country->phone_code }}</option>
                                            @endforeach
                                        </select>
                                        <input type="tel" wire:model="phone" id="phone" autocomplete="tel-national" placeholder="1XXXXXXXXX"
                                            class="flex-1 min-w-0 p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    @error('country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="phone_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" wire:model="phone_password" id="phone_password" autocomplete="current-password"
                                        class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('phone_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-xs text-gray-500">Leave the phone empty to remove it. A new number gets a verification code by SMS.</p>
                                <button type="submit" wire:loading.attr="disabled" wire:target="updatePhone"
                                    class="shrink-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="updatePhone">Change Phone</span>
                                    <span wire:loading wire:target="updatePhone">Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Password --}}
                    <form wire:submit.prevent="updatePassword" class="pt-6 space-y-4">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Password</h3>
                            <p class="text-sm text-gray-500">Last changed: {{ local_time($user->password_set_at)?->format('M d, Y') ?? '—' }}</p>
                        </div>

                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" wire:model="current_password" id="current_password" autocomplete="current-password"
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" wire:model="password" id="password" autocomplete="new-password"
                                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" wire:model="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60">
                                <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                                <span wire:loading wire:target="updatePassword">Saving…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>


            {{-- Two-Factor Methods --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900">Two-Factor Login Methods</h2>
                <p class="text-sm text-gray-500 mb-5">
                    After your password, you'll be asked for a code from any method that is on.
                    You can pick which one to use on the login screen.
                </p>

                <form wire:submit.prevent="updateTwoFactorMethods" class="space-y-4">
                    <div class="divide-y divide-gray-100 rounded-md border border-gray-200">
                        {{-- Authenticator app (managed by the Jetstream card below) --}}
                        <div class="flex items-center justify-between gap-4 p-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">Authenticator app</p>
                                <p class="text-xs text-gray-500">Google Authenticator, Microsoft Authenticator, Authy. Strongest option, works offline.</p>
                            </div>
                            @if($user->hasAuthenticatorTwoFactor())
                                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">On</span>
                            @else
                                <span class="shrink-0 text-xs text-gray-500">Set up below ↓</span>
                            @endif
                        </div>

                        {{-- Email code --}}
                        <label class="flex items-center justify-between gap-4 p-4 {{ $user->email_verified_at ? 'cursor-pointer' : 'opacity-60' }}">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">Email code</p>
                                <p class="text-xs text-gray-500 break-all">
                                    @if($user->email_verified_at)
                                        A 6-digit code is emailed to {{ $user->email }}.
                                    @else
                                        Verify your email above to use this.
                                    @endif
                                </p>
                                @error('two_factor_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <input type="checkbox" wire:model="two_factor_email" @disabled(!$user->email_verified_at && !$two_factor_email)
                                class="h-5 w-5 shrink-0 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </label>

                        {{-- SMS code --}}
                        <label class="flex items-center justify-between gap-4 p-4 {{ $user->phone && $user->phone_verified_at ? 'cursor-pointer' : 'opacity-60' }}">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">SMS code</p>
                                <p class="text-xs text-gray-500">
                                    @if($user->phone && $user->phone_verified_at)
                                        A 6-digit code is sent by SMS to {{ trim($user->country_code . ' ' . $user->phone) }}.
                                    @else
                                        Add and verify a phone number above to use this.
                                    @endif
                                </p>
                                @error('two_factor_sms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <input type="checkbox" wire:model="two_factor_sms" @disabled(!($user->phone && $user->phone_verified_at) && !$two_factor_sms)
                                class="h-5 w-5 shrink-0 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </label>
                    </div>

                    @if(($user->two_factor_email_enabled && !$user->email_verified_at) || ($user->two_factor_sms_enabled && !$user->phone_verified_at))
                        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3">
                            A method is on but its email/phone is not verified, so it is paused until you verify it.
                        </p>
                    @endif

                    <div class="flex flex-col sm:flex-row sm:items-end justify-end gap-3">
                        <div class="sm:w-64">
                            <label for="two_factor_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" wire:model="two_factor_password" id="two_factor_password" autocomplete="current-password"
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('two_factor_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateTwoFactorMethods"
                            class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60">
                            <span wire:loading.remove wire:target="updateTwoFactorMethods">Save Methods</span>
                            <span wire:loading wire:target="updateTwoFactorMethods">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Two-Factor Authentication --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::twoFactorAuthentication()))
                    @livewire('profile.two-factor-authentication-form')
                @else
                    <p class="text-sm text-gray-500">Two-factor authentication is currently disabled in system features.</p>
                @endif
            </div>

            {{-- Browser Sessions --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                @livewire('profile.logout-other-browser-sessions-form')
            </div>

        </div>
        {{-- ======================== Content End Here ======================== --}}
    </div>
</div>
