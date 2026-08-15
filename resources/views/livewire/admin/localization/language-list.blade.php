<div x-data x-init="$store.pageName = { name: 'Languages', slug: 'settings-languages' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <a href="{{ route('admin.settings.languages.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Language
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-700">{{ $languages->count() }} {{ Str::plural('language', $languages->count()) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Manage which languages are available on the public site, and pick the default</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Language</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Direction</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Active</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Default</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($languages as $language)
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    @if($language->flag_emoji)
                                        <span class="text-base">{{ $language->flag_emoji }}</span>
                                    @endif
                                    <div>
                                        <span class="text-sm font-medium text-gray-800">{{ $language->name }}</span>
                                        <span class="block text-xs text-gray-400">{{ $language->native_name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">{{ $language->code }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ $language->direction === 'rtl' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ strtoupper($language->direction) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button wire:click="toggleActive({{ $language->id }})" type="button"
                                    title="{{ $language->is_active ? 'Click to deactivate' : 'Click to activate' }}"
                                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1
                                        {{ $language->is_active ? 'bg-indigo-500 focus:ring-indigo-400' : 'bg-gray-300 focus:ring-gray-400' }}">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform
                                        {{ $language->is_active ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($language->is_default)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        Default
                                    </span>
                                @else
                                    <button wire:click="setDefault({{ $language->id }})" type="button"
                                        class="text-xs font-medium text-gray-500 hover:text-indigo-600 transition">
                                        Make Default
                                    </button>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.settings.languages.edit', $language->id) }}"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                        </svg>
                                    </a>
                                    <button type="button" x-data
                                        @click="Swal.fire({
                                            title: 'Delete language?',
                                            text: '{{ addslashes($language->name) }} will be removed permanently.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Delete'
                                        }).then(r => { if (r.isConfirmed) $wire.deleteLanguage({{ $language->id }}) })"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition
                                            {{ $language->is_default ? 'opacity-30 cursor-not-allowed' : '' }}"
                                        {{ $language->is_default ? 'disabled' : '' }}>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No languages defined</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Add a language to make it available on the public site</p>
                                    </div>
                                    <a href="{{ route('admin.settings.languages.create') }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        Add First Language
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
