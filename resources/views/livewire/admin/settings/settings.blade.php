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
                        <span class="text-sm text-gray-900">{{ local_time($user->created_at)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Last Updated</span>
                        <span class="text-sm text-gray-900">{{ local_time($user->updated_at)->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
            {{-- Security Settings --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Security Settings</h2>
                <form wire:submit.prevent="updatePassword" class="space-y-6">

                    {{-- Current Password --}}
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" wire:model="current_password" id="current_password"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- New Password --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" wire:model="password" id="password"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" wire:model="password_confirmation" id="password_confirmation"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password_confirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Password
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
