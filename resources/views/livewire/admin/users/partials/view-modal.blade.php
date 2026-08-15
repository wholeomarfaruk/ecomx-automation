{{-- ── View / Edit User Modal ── --}}
<div x-cloak
     x-data="{ modalOpen: @entangle('viewModal'), editMode: false }"
     x-show="modalOpen"
     x-transition
     @user-saved.window="editMode = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     role="dialog" aria-modal="true">

    <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <h2 class="text-base font-semibold text-gray-900"
                x-text="editMode ? 'Edit User' : 'User Details'"></h2>
            <div class="flex items-center gap-1">
                <button @click="editMode = !editMode" type="button"
                    :class="editMode ? 'text-red-500 hover:bg-red-50' : 'text-indigo-600 hover:bg-indigo-50'"
                    class="w-8 h-8 flex items-center justify-center rounded-full transition"
                    :title="editMode ? 'Cancel edit' : 'Edit user'">
                    <template x-if="!editMode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                        </svg>
                    </template>
                    <template x-if="editMode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </template>
                </button>
                <button @click="modalOpen = false; editMode = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Scrollable body --}}
        <div class="overflow-y-auto flex-1">

            {{-- Profile hero --}}
            <div class="px-6 py-6 flex flex-wrap items-center gap-5 bg-linear-to-br from-indigo-50 via-white to-white">

                {{-- Avatar --}}
                @php
                    $modalAvatarUrl = $avatar_id ? (file_path($avatar_id) ?? null) : null;
                    $modalAvatarUrl ??= 'https://ui-avatars.com/api/?name=' . urlencode($user?->name ?? 'U') . '&background=6366f1&color=ffffff&bold=true&size=128';
                @endphp
                <div class="relative group shrink-0">
                    @if($avatar_id)
                        <button type="button"
                           @click.stop="Fancybox.show([{ src: '{{ $modalAvatarUrl }}', type: 'image' }])"
                           class="absolute -bottom-1 -right-1 z-20 w-6 h-6 rounded-full bg-white border border-gray-200 shadow flex items-center justify-center text-gray-500 hover:text-indigo-600 transition"
                           title="View photo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    @endif
                    <div class="cursor-pointer"
                         wire:click="$dispatch('openMediaPicker', { target: 'avatar_id', multiple: false, type: 'image' })">
                        <img src="{{ $modalAvatarUrl }}" alt="{{ $user?->name }}"
                            class="w-24 h-24 rounded-full object-cover ring-4 ring-white shadow-lg">
                        <div class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100
                                    transition flex flex-col items-center justify-center gap-1 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/>
                            </svg>
                            <span class="text-[10px] font-semibold tracking-wide">Change</span>
                        </div>
                    </div>
                    @if($avatar_id)
                        <button type="button" wire:click.stop="removeAvatar" title="Remove photo"
                            class="absolute -top-1 -right-1 z-10 w-5 h-5 rounded-full bg-red-500 text-white
                                   flex items-center justify-center shadow-md transition
                                   opacity-70 group-hover:opacity-100 hover:bg-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- View mode info --}}
                <div class="flex-1 min-w-0" x-show="!editMode">
                    <h3 class="text-xl font-bold text-gray-900 truncate">{{ $user?->name ?? '—' }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $user?->email ?? '' }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-2.5">
                        @if($user?->roles?->first())
                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full font-medium bg-indigo-100 text-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                {{ $user->roles->first()->name }}
                            </span>
                        @else
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500">No Role</span>
                        @endif
                        <span class="text-xs text-gray-400">Joined {{ local_time($user?->created_at)?->format('d M Y') ?? '' }}</span>
                    </div>
                </div>

                {{-- Edit mode inputs --}}
                <div class="flex-1 min-w-0 space-y-2" x-show="editMode" x-cloak>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                        <input wire:model="editName" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            placeholder="Full name">
                        @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input wire:model="editEmail" type="email"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            placeholder="Email address">
                        @error('editEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Edit mode: password --}}
            <div x-show="editMode" x-cloak class="px-6 pb-4 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">
                    Change Password
                    <span class="normal-case font-normal text-gray-400">(leave blank to keep current)</span>
                </p>
                <input wire:model="editPassword" type="password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                    placeholder="New password (min 8 characters)">
                @error('editPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- ── Profile Details section ── --}}
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Profile Details</p>

                {{-- VIEW MODE --}}
                <div x-show="!editMode" class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                    @if($user?->gender)
                        <div>
                            <span class="text-xs text-gray-400">Gender</span>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">
                                {{ $genders->firstWhere('slug', $user->gender)?->name ?? ucfirst(str_replace('_', ' ', $user->gender)) }}
                            </p>
                        </div>
                    @endif

                    @if($user?->phone)
                        <div>
                            <span class="text-xs text-gray-400">Phone</span>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">
                                {{ $user->country_code ? $user->country_code . ' ' : '' }}{{ $user->phone }}
                            </p>
                        </div>
                    @endif

                    @if($user?->address)
                        <div class="col-span-2">
                            <span class="text-xs text-gray-400">Address</span>
                            <p class="text-sm font-medium text-gray-800 mt-0.5">{{ $user->address }}</p>
                        </div>
                    @endif

                    @if($user?->bio)
                        <div class="col-span-2">
                            <span class="text-xs text-gray-400">Bio</span>
                            <p class="text-sm text-gray-600 mt-0.5 leading-relaxed">{{ $user->bio }}</p>
                        </div>
                    @endif

                    @if(! $user?->gender && ! $user?->phone && ! $user?->address && ! $user?->bio)
                        <p class="col-span-2 text-sm text-gray-400 italic">No additional info — click edit to add.</p>
                    @endif
                </div>

                {{-- EDIT MODE --}}
                <div x-show="editMode" x-cloak class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Gender</label>
                        <select wire:model="editGender"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
                            <option value="">— Not specified —</option>
                            @foreach ($genders as $genderItem)
                                <option value="{{ $genderItem->slug }}">{{ $genderItem->name }}</option>
                            @endforeach
                        </select>
                        @error('editGender') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number</label>
                        <div class="flex gap-2">
                            @include('livewire.admin.users.partials._phone-code-picker', [
                                'wireProperty' => 'editCountryCode',
                                'inputClass'   => 'w-32 shrink-0',
                            ])
                            <input wire:model="editPhone" type="text"
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                placeholder="Phone number">
                        </div>
                        @error('editCountryCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        @error('editPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                        <input wire:model="editAddress" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            placeholder="Street, city, country">
                        @error('editAddress') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bio</label>
                        <textarea wire:model="editBio" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 resize-none"
                            placeholder="Short bio about the user…"></textarea>
                        @error('editBio') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ── Verification Status section ── --}}
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Verification</p>

                <div class="space-y-3">

                    {{-- Email verification --}}
                    <div class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-xl bg-gray-50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                {{ $user?->email_verified_at ? 'bg-emerald-100' : 'bg-red-100' }}">
                                @if($user?->email_verified_at)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Email</p>
                                @if($user?->email_verified_at)
                                    <p class="text-xs text-emerald-600">Verified on {{ local_time($user->email_verified_at)->format('d M Y') }}</p>
                                @else
                                    <p class="text-xs text-red-500">Not verified</p>
                                @endif
                            </div>
                        </div>

                        @if(! $user?->email_verified_at)
                            <button wire:click="markEmailVerified({{ $user?->id }})" type="button"
                                class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                Mark Verified
                            </button>
                        @else
                            <div class="shrink-0 flex items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified
                                </span>
                                <button wire:click="revokeEmailVerification({{ $user->id }})" type="button"
                                    title="Remove verification"
                                    class="w-5 h-5 inline-flex items-center justify-center rounded-full text-gray-400 hover:bg-red-100 hover:text-red-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Phone verification --}}
                    <div class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-xl bg-gray-50">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                {{ $user?->phone_verified_at ? 'bg-emerald-100' : ($user?->phone ? 'bg-amber-100' : 'bg-gray-200') }}">
                                @if($user?->phone_verified_at)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $user?->phone ? 'text-amber-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Phone
                                    @if($user?->phone)
                                        <span class="text-xs text-gray-400 font-normal ml-1">
                                            {{ $user->country_code ? $user->country_code . ' ' : '' }}{{ $user->phone }}
                                        </span>
                                    @endif
                                </p>
                                @if(! $user?->phone)
                                    <p class="text-xs text-gray-400">No phone number set</p>
                                @elseif($user->phone_verified_at)
                                    <p class="text-xs text-emerald-600">Verified on {{ local_time($user->phone_verified_at)->format('d M Y') }}</p>
                                @else
                                    <p class="text-xs text-amber-600">Not verified</p>
                                @endif
                            </div>
                        </div>

                        @if($user?->phone && ! $user->phone_verified_at)
                            <button wire:click="markPhoneVerified({{ $user->id }})" type="button"
                                class="shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition">
                                Mark Verified
                            </button>
                        @elseif($user?->phone_verified_at)
                            <div class="shrink-0 flex items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified
                                </span>
                                <button wire:click="revokePhoneVerification({{ $user->id }})" type="button"
                                    title="Remove verification"
                                    class="w-5 h-5 inline-flex items-center justify-center rounded-full text-gray-400 hover:bg-red-100 hover:text-red-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <span class="shrink-0 text-xs text-gray-400 italic">No number</span>
                        @endif
                    </div>

                </div>
            </div>

            {{-- ── Role & Panels section ── --}}
            <div class="px-6 py-4 space-y-5">

                {{-- Role --}}
                <div class="flex items-start gap-4">
                    <label class="text-sm font-medium text-gray-700 w-28 shrink-0 pt-2">Role</label>
                    <div class="flex-1">
                        <select wire:model.live="role_name"
                            @if($role_name === 'superadmin') disabled @endif
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                            <option value="">— No role —</option>
                            @foreach ($roles as $item)
                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @if($role_name === 'superadmin')
                            <p class="text-xs text-amber-600 mt-1">Superadmin role cannot be changed</p>
                        @endif
                    </div>
                </div>

                {{-- Panels --}}
                <div class="flex items-start gap-4">
                    <span class="text-sm font-medium text-gray-700 w-28 shrink-0 pt-1">Panels</span>
                    <div class="flex-1">
                        @if($panels->isEmpty())
                            <p class="text-sm text-gray-400 italic">No panels available</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($panels as $panel_item)
                                    @php $isChecked = in_array($panel_item->id, $panelIds); @endphp
                                    <button type="button"
                                        wire:click="togglePanel({{ $panel_item->id }})"
                                        @if($role_name === 'superadmin') disabled @endif
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition
                                            {{ $isChecked
                                                ? 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700'
                                                : 'bg-white border-gray-300 text-gray-600 hover:border-indigo-400 hover:text-indigo-600' }}
                                            disabled:opacity-40 disabled:cursor-not-allowed">
                                        @if($isChecked)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ $panel_item->name }}
                                    </button>
                                @endforeach
                            </div>
                            @if($role_name === 'superadmin')
                                <p class="text-xs text-amber-600 mt-1.5">Superadmin has access to all panels</p>
                            @elseif(empty($panelIds))
                                <p class="text-xs text-gray-400 mt-1.5">No panels assigned — click to assign</p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2 shrink-0">
            <template x-if="!editMode">
                <button @click="modalOpen = false" type="button"
                    class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Close
                </button>
            </template>
            <template x-if="editMode">
                <div class="flex gap-2">
                    <button @click="editMode = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button wire:click="updateUser" type="button"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H9V3m0 11a3 3 0 1 0 6 0 3 3 0 0 0-6 0"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </template>
        </div>

    </div>
</div>
