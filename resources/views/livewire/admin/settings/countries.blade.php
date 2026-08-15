<div x-data x-init="$store.pageName = { name: 'Countries', slug: 'settings-countries' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <button wire:click="$set('createModal', true)" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Country
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative w-full sm:flex-1 sm:min-w-52">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.400ms="search" type="text"
                    placeholder="Search by name, code or phone code…"
                    class="w-full rounded-lg border border-gray-300 pl-9 pr-4 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <select wire:model.live="filterAllowed"
                class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Registration</option>
                <option value="1">Allowed</option>
                <option value="0">Restricted</option>
            </select>

            <select wire:model.live="filterActive"
                class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white text-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>

            <span class="text-sm text-gray-400 sm:ml-auto whitespace-nowrap">{{ $countries->total() }} countries</span>
        </div>

        {{-- Legend --}}
        <div class="px-5 py-2.5 bg-gray-50/60 border-b border-gray-100 flex flex-wrap items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span> Registration allowed
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-gray-300"></span> Registration restricted
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-indigo-400"></span> Country active
            </span>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Country</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Phone</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Currency</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Timezone</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Registration</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Active</th>
                        <th class="sticky right-0 bg-gray-50/40 px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($countries as $country)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xl leading-none w-7 text-center">{{ $country->emoji_flag ?? '🌐' }}</span>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-800">{{ $country->name }}</span>
                                        @if($country->local_name)
                                            <span class="text-xs text-gray-400">{{ $country->local_name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-gray-100 text-gray-700 tracking-wider">
                                    {{ $country->code }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600 font-mono">{{ $country->phone_code }}</td>
                            <td class="px-5 py-3">
                                @if($country->currency_code)
                                    <span class="text-xs text-gray-500 font-mono">{{ $country->currency_code }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($country->timezone)
                                    <span class="text-xs text-gray-500 font-mono">{{ $country->timezone }}</span>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>

                            {{-- Registration toggle --}}
                            <td class="px-5 py-3 text-center">
                                <button wire:click="toggleRegisterAllowed({{ $country->id }})" type="button"
                                    title="{{ $country->is_register_allowed ? 'Click to restrict' : 'Click to allow' }}"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1
                                        {{ $country->is_register_allowed ? 'bg-emerald-500 focus:ring-emerald-400' : 'bg-gray-300 focus:ring-gray-400' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $country->is_register_allowed ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>

                            {{-- Active toggle --}}
                            <td class="px-5 py-3 text-center">
                                <button wire:click="toggleActive({{ $country->id }})" type="button"
                                    title="{{ $country->is_active ? 'Click to deactivate' : 'Click to activate' }}"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1
                                        {{ $country->is_active ? 'bg-indigo-500 focus:ring-indigo-400' : 'bg-gray-300 focus:ring-gray-400' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $country->is_active ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 bg-white group-hover:bg-gray-50/50 px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button wire:click="editCountry({{ $country->id }})" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                        </svg>
                                    </button>
                                    <button type="button" x-data
                                        @click="Swal.fire({
                                            title: 'Delete country?',
                                            text: '{{ addslashes($country->name) }} will be removed.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Delete'
                                        }).then(r => { if (r.isConfirmed) $wire.deleteCountry({{ $country->id }}) })"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center text-2xl">🌐</div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No countries found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Run the CountrySeeder or add one manually</p>
                                    </div>
                                    <button wire:click="$set('createModal', true)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        Add Country
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($countries->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $countries->links() }}
            </div>
        @endif
    </div>

    {{-- Create Modal --}}
    <div x-cloak x-data="{ open: @entangle('createModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Add Country</h2>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="createCountry" class="px-6 py-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Country Name <span class="text-red-500">*</span></label>
                        <input wire:model="newName" type="text" placeholder="e.g. Bangladesh"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Local Name</label>
                        <input wire:model="newLocalName" type="text" placeholder="e.g. বাংলাদেশ"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newLocalName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">ISO Code <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(2 letters)</span></label>
                        <input wire:model="newCode" type="text" placeholder="BD" maxlength="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase tracking-widest font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Emoji Flag</label>
                        <input wire:model="newEmojiFlag" type="text" placeholder="🇧🇩"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-center text-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Phone Code <span class="text-red-500">*</span></label>
                        <input wire:model="newPhoneCode" type="text" placeholder="+880"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('newPhoneCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Currency Code</label>
                        <input wire:model="newCurrencyCode" type="text" placeholder="BDT"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Timezone</label>
                    <select wire:model="newTimezone"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select timezone —</option>
                        @foreach($timezoneOptions as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('newTimezone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Country
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-cloak x-data="{ open: @entangle('editModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Edit Country</h2>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="updateCountry" class="px-6 py-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Country Name <span class="text-red-500">*</span></label>
                        <input wire:model="editName" type="text" placeholder="e.g. Bangladesh"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Local Name</label>
                        <input wire:model="editLocalName" type="text" placeholder="e.g. বাংলাদেশ"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editLocalName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">ISO Code <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(2 letters)</span></label>
                        <input wire:model="editCode" type="text" placeholder="BD" maxlength="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm uppercase tracking-widest font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Emoji Flag</label>
                        <input wire:model="editEmojiFlag" type="text" placeholder="🇧🇩"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-center text-xl focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Phone Code <span class="text-red-500">*</span></label>
                        <input wire:model="editPhoneCode" type="text" placeholder="+880"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editPhoneCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Currency Code</label>
                        <input wire:model="editCurrencyCode" type="text" placeholder="BDT"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Timezone</label>
                    <select wire:model="editTimezone"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select timezone —</option>
                        @foreach($timezoneOptions as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('editTimezone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
