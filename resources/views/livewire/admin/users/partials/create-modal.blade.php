{{-- ── Create User Modal ── --}}
<div x-cloak
     x-data="{ modalOpen: @entangle('UserModal') }"
     x-show="modalOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     role="dialog" aria-modal="true">

    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Add New User</h2>
                    <p class="text-xs text-gray-400">Fill in the details to create a new account</p>
                </div>
            </div>
            <button @click="modalOpen = false" type="button"
                class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div class="overflow-y-auto flex-1">
            <form wire:submit.prevent="registerUser" id="create-user-form">

                {{-- ── Account Credentials ── --}}
                <div class="px-6 py-5 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Account Credentials</p>
                    <div class="space-y-4">

                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="newUserName">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="newUserName" id="newUserName" type="text" autocomplete="off"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                placeholder="e.g. John Doe">
                            @error('newUserName') <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1"><svg class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="newUserEmail">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="newUserEmail" id="newUserEmail" type="email" autocomplete="off"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                placeholder="email@example.com">
                            @error('newUserEmail') <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1"><svg class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5" for="newUserPassword">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <div x-data="{ show: false }" class="relative">
                                <input wire:model="newUserPassword" id="newUserPassword"
                                    :type="show ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 pr-11 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                    placeholder="Min. 8 characters">
                                <button type="button" @click="show = !show"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                                    <template x-if="!show">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </template>
                                    <template x-if="show">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                        </svg>
                                    </template>
                                </button>
                            </div>
                            @error('newUserPassword') <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1"><svg class="h-3 w-3 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>

                {{-- ── Role & Access ── --}}
                <div class="px-6 py-5 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Role & Access</p>
                    <div class="space-y-4">

                        {{-- Role --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                            <select wire:model="newUserRole"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition bg-white text-gray-700">
                                <option value="">— No role assigned —</option>
                                @foreach ($roles as $roleItem)
                                    <option value="{{ $roleItem->name }}">{{ ucfirst($roleItem->name) }}</option>
                                @endforeach
                            </select>
                            @error('newUserRole') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Panels --}}
                        @if($panels->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Panels</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($panels as $panelItem)
                                    @php $isPanelChecked = in_array($panelItem->id, $newUserPanels); @endphp
                                    <button type="button"
                                        wire:click="toggleNewUserPanel({{ $panelItem->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                            {{ $isPanelChecked
                                                ? 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700'
                                                : 'bg-white border-gray-300 text-gray-600 hover:border-indigo-400 hover:text-indigo-600' }}">
                                        @if($isPanelChecked)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $panelItem->name }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Click to toggle panel access</p>
                        </div>
                        @endif

                    </div>
                </div>

                {{-- ── Profile Info ── --}}
                <div class="px-6 py-5">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Profile Info <span class="normal-case font-normal text-gray-400">(optional)</span></p>
                    <div class="space-y-4">

                        {{-- Gender --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender</label>
                            <select wire:model="newUserGender"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition bg-white text-gray-700">
                                <option value="">— Not specified —</option>
                                @foreach ($genders as $genderItem)
                                    <option value="{{ $genderItem->slug }}">{{ $genderItem->name }}</option>
                                @endforeach
                            </select>
                            @error('newUserGender') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                            <div class="flex gap-2">
                                @include('livewire.admin.users.partials._phone-code-picker', [
                                    'wireProperty' => 'newUserCountryCode',
                                    'inputClass'   => 'w-36 shrink-0',
                                ])
                                <input wire:model="newUserPhone" type="text"
                                    class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                    placeholder="Phone number">
                            </div>
                            @error('newUserCountryCode') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                            @error('newUserPhone') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                            <input wire:model="newUserAddress" type="text"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                placeholder="Street, city, country">
                            @error('newUserAddress') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                        {{-- Bio --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Bio</label>
                            <textarea wire:model="newUserBio" rows="3"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition resize-none"
                                placeholder="Short bio about the user…"></textarea>
                            @error('newUserBio') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                        </div>

                    </div>
                </div>

            </form>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between gap-3 shrink-0">
            <p class="text-xs text-gray-400">
                <span class="text-red-400">*</span> Required fields
            </p>
            <div class="flex items-center gap-2">
                <button @click="modalOpen = false" type="button"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" form="create-user-form"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition inline-flex items-center gap-2 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                    </svg>
                    Create User
                </button>
            </div>
        </div>

    </div>
</div>
