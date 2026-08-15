@php $isEdit = $languageId > 0; @endphp
<div x-data x-init="$store.pageName = { name: '{{ $isEdit ? 'Edit Language' : 'Add Language' }}', slug: 'settings-language-form' }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <a href="{{ route('admin.settings.languages') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Languages
        </a>
    </div>

    <form wire:submit="save">
        <div class="max-w-2xl">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">{{ $isEdit ? 'Edit Language' : 'Add Language' }}</h2>
                    <p class="text-xs text-gray-400">{{ $isEdit ? 'Update the language details below' : 'Add a new language for the public site' }}</p>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Language Code <span class="text-red-500">*</span></label>
                            <input wire:model="code" type="text" placeholder="e.g. en, bn, ar"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Direction <span class="text-red-500">*</span></label>
                            <select wire:model="direction"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="ltr">Left to Right (LTR)</option>
                                <option value="rtl">Right to Left (RTL)</option>
                            </select>
                            @error('direction') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name (English) <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="e.g. Arabic"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Native Name <span class="text-red-500">*</span></label>
                            <input wire:model="native_name" type="text" placeholder="e.g. العربية"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('native_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 px-4 py-3.5">
                        <div>
                            <p class="text-sm font-medium text-gray-800">Active</p>
                            <p class="text-xs text-gray-400 mt-0.5">Inactive languages won't appear on the public site</p>
                        </div>
                        <button wire:click="$toggle('is_active')" type="button"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-all focus:outline-none
                                {{ $is_active ? 'bg-indigo-500' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                {{ $is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                    @error('is_active') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2">
                    <a href="{{ route('admin.settings.languages') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                        {{ $isEdit ? 'Update Language' : 'Add Language' }}
                    </button>
                </div>

            </div>
        </div>
    </form>

</div>
