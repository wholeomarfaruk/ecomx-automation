{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'My Profile', slug: 'profile' }">
    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh] p-6">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Profile Overview --}}
            <div class="bg-gray-50 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Profile Overview</h2>
                <div class="flex items-start space-x-6">
                    <div class="flex-shrink-0">
                        <div class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h3>
                        <p class="text-gray-600">{{ $user->email }}</p>
                        @if($user->phone)
                            <p class="text-gray-600">{{ $user->country_code }} {{ $user->phone }}</p>
                        @endif
                        @if($user->roles->count() > 0)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                        @if($user->email_verified_at)
                            <p class="text-sm text-green-600 mt-1">✓ Email verified</p>
                        @else
                            <p class="text-sm text-red-600 mt-1">✗ Email not verified</p>
                        @endif
                        @if($user->phone_verified_at)
                            <p class="text-sm text-green-600">✓ Phone verified</p>
                        @else
                            <p class="text-sm text-gray-500">Phone not verified</p>
                        @endif
                    </div>
                </div>
                @if($user->bio)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-900">Bio</h4>
                        <p class="text-gray-600 mt-1">{{ $user->bio }}</p>
                    </div>
                @endif
                @if($user->address)
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-900">Address</h4>
                        <p class="text-gray-600 mt-1">{{ $user->address }}</p>
                    </div>
                @endif
                <div class="mt-4 text-sm text-gray-500">
                    <p>Member since: {{ local_time($user->created_at)->format('M d, Y') }}</p>
                    <p>Last updated: {{ local_time($user->updated_at)->format('M d, Y') }}</p>
                </div>
            </div>

            {{-- Edit Profile Form --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Edit Profile</h2>
                <form wire:submit.prevent="updateProfile" class="space-y-6">

                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" id="name"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="email" id="email"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="country_code" class="block text-sm font-medium text-gray-700">Country Code</label>
                            <input type="text" wire:model="country_code" id="country_code" placeholder="+88" 
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('country_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" wire:model="phone" id="phone"
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea wire:model="address" id="address" rows="3"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Bio --}}
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                        <textarea wire:model="bio" id="bio" rows="4"
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>


        </div>
        {{-- ======================== Content End Here ======================== --}}
    </div>
</div>