<div x-data x-init="$store.pageName = { name: 'Site Settings', slug: 'site-settings' }">

    <div class="flex flex-col lg:flex-row gap-6 items-start">

        {{-- ── Sidebar ── --}}
        <div class="w-full lg:w-56 shrink-0">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-2 flex lg:block gap-1 overflow-x-auto">
                @php
                    $navItems = [
                        'application'  => ['label' => 'Application',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z"/>'],
                        'general'      => ['label' => 'General',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>'],
                        'company'      => ['label' => 'Company Information', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>'],
                        'localization' => ['label' => 'Localization',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>'],
                        'mail'         => ['label' => 'Email',          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>'],
                        'social'       => ['label' => 'Social Links',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>'],
                        'registration' => ['label' => 'Registration',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>'],
                        'pricing'      => ['label' => 'Pricing',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.553-.44 1.278-.659 2.003-.659.725 0 1.45.22 2.003.659l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>'],
                    ];
                @endphp

                @foreach ($navItems as $group => $item)
                    <button wire:click="setGroup('{{ $group }}')" type="button"
                        class="shrink-0 lg:w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all
                            {{ $activeGroup === $group
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 shrink-0 {{ $activeGroup === $group ? 'text-white' : 'text-gray-400' }}"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            {!! $item['icon'] !!}
                        </svg>
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </div>

            {{-- Quick links --}}
            <div class="mt-4 bg-white rounded-2xl shadow-sm border border-gray-200 p-3 space-y-1">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-2 mb-2">Quick Links</p>
                <a href="{{ route('admin.settings.languages') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802"/>
                    </svg>
                    Languages
                </a>
                <a href="{{ route('admin.settings.countries') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                    </svg>
                    Countries
                </a>
                <a href="{{ route('admin.settings.states') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
                    </svg>
                    States
                </a>
                <a href="{{ route('admin.settings.cities') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                    Cities
                </a>
                <a href="{{ route('admin.settings.genders') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                    Gender List
                </a>
                <a href="{{ route('admin.settings.currencies') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.553-.44 1.278-.659 2.003-.659.725 0 1.45.22 2.003.659l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Currencies
                </a>
                <a href="{{ route('admin.settings.branches') }}"
                    class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                    Branches
                </a>
            </div>
        </div>

        {{-- ── Settings Panel ── --}}
        <div class="flex-1 min-w-0">
            <form wire:submit.prevent="save">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

                    {{-- ══════════════════ APPLICATION ══════════════════ --}}
                    @if ($activeGroup === 'application')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Application Settings</h2>
                                <p class="text-xs text-gray-400">Runtime info and application-level controls</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Short Name</label>
                                    <input wire:model="site_short_name" type="text" placeholder="e.g. LSK" maxlength="50"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('site_short_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    <p class="text-xs text-gray-400 mt-1.5">A shorter label used where space is limited (e.g. mobile nav, browser tab).</p>
                                </div>
                            </div>

                            {{-- Version Info --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Version Info</p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Application Version</label>
                                        <input type="text" value="{{ config('app.version') }}" disabled
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Environment</label>
                                        <input type="text" value="{{ config('app.env') }}" disabled
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 capitalize">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Debug Mode</label>
                                        <div class="px-3 py-2.5">
                                            <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                                                {{ config('app.debug') ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ config('app.debug') ? 'ON' : 'OFF' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1.5">These reflect the server's .env configuration and cannot be changed here.</p>
                            </div>

                            {{-- Maintenance Mode --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Maintenance</p>
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 px-4 py-3.5
                                    {{ $maintenance_mode ? 'bg-red-50 border-red-200' : 'bg-white' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex mt-0.5 text-xs font-semibold px-2 py-0.5 rounded-full
                                            {{ $maintenance_mode ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $maintenance_mode ? 'ON' : 'OFF' }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">Maintenance Mode</p>
                                            <p class="text-xs text-gray-400 mt-0.5">When enabled, the public site is inaccessible to visitors. Applies immediately.</p>
                                        </div>
                                    </div>
                                    <button wire:click="toggleMaintenanceMode" type="button"
                                        wire:confirm="{{ $maintenance_mode
                                            ? 'Bring the application back online?'
                                            : 'Put the application into maintenance mode? The public site will be inaccessible to visitors until you turn this off.' }}"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-all focus:outline-none
                                            {{ $maintenance_mode ? 'bg-red-500 ring-2 ring-red-200' : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                            {{ $maintenance_mode ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ GENERAL ══════════════════ --}}
                    @if ($activeGroup === 'general')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">General Settings</h2>
                                <p class="text-xs text-gray-400">Site identity and branding</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Site Name <span class="text-red-500">*</span></label>
                                    <input wire:model="site_name" type="text" placeholder="My App"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('site_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tagline</label>
                                    <input wire:model="site_tagline" type="text" placeholder="Short description"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                            </div>

                            {{-- Branding --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Branding</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <x-media-picker-field
                                        field="site_logo"
                                        :value="$site_logo"
                                        label="Logo (Normal)"
                                        type="image"
                                        placeholder="Select logo" />
                                    <x-media-picker-field
                                        field="site_logo_black"
                                        :value="$site_logo_black"
                                        label="Logo (Black)"
                                        type="image"
                                        placeholder="Select black logo" />
                                    <x-media-picker-field
                                        field="site_logo_white"
                                        :value="$site_logo_white"
                                        label="Logo (White)"
                                        type="image"
                                        placeholder="Select white logo" />
                                    <x-media-picker-field
                                        field="site_logo_symbol"
                                        :value="$site_logo_symbol"
                                        label="Logo (Symbol / Icon)"
                                        type="image"
                                        placeholder="Select symbol/icon" />
                                    <x-media-picker-field
                                        field="site_favicon"
                                        :value="$site_favicon"
                                        label="Favicon"
                                        type="image"
                                        placeholder="Select favicon" />
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ COMPANY INFORMATION ══════════════════ --}}
                    @if ($activeGroup === 'company')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-900">Company Information</h2>
                                    <p class="text-xs text-gray-400">Legal and business details used on official documents</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.company.print') }}" target="_blank"
                                class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                                </svg>
                                View / Print
                            </a>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Company Name <span class="text-red-500">*</span></label>
                                    <input wire:model="company_name" type="text" placeholder="e.g. Acme Corp"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('company_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Legal Name</label>
                                    <input wire:model="company_legal_name" type="text" placeholder="e.g. Acme Corporation Ltd."
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('company_legal_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            {{-- Branding --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Branding</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <x-media-picker-field
                                        field="company_logo"
                                        :value="$company_logo"
                                        label="Logo"
                                        type="image"
                                        placeholder="Select logo" />
                                    <x-media-picker-field
                                        field="company_favicon"
                                        :value="$company_favicon"
                                        label="Favicon"
                                        type="image"
                                        placeholder="Select favicon" />
                                </div>
                            </div>

                            {{-- Contact --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Contact</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                                        <input wire:model="company_email" type="email" placeholder="info@example.com"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Website</label>
                                        <input wire:model="company_website" type="url" placeholder="https://example.com"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_website') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                                        <input wire:model="company_phone" type="text" placeholder="+1 234 567 890"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Mobile</label>
                                        <input wire:model="company_mobile" type="text" placeholder="+1 234 567 890"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_mobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Address --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Address</p>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                                        <input wire:model="company_address" type="text" placeholder="Street address"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                                            <input wire:model="company_city" type="text" placeholder="City"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">State</label>
                                            <input wire:model="company_state" type="text" placeholder="State / Province"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Country</label>
                                            <select wire:model="company_country_id"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <option value="">Select country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('company_country_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Postal Code</label>
                                            <input wire:model="company_postal_code" type="text" placeholder="Postal / ZIP code"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Map Location</label>
                                        <input wire:model="company_map_location" type="text" placeholder="Google Maps URL or embed link"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @error('company_map_location') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        <p class="text-xs text-gray-400 mt-1.5">Paste a Google Maps share link or embed URL for this location.</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Legal / Tax --}}
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Legal / Tax</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tax / VAT Number</label>
                                        <input wire:model="company_tax_number" type="text" placeholder="e.g. VAT-123456"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Trade License Number</label>
                                        <input wire:model="company_trade_license" type="text" placeholder="e.g. TL-987654"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ LOCALIZATION ══════════════════ --}}
                    @if ($activeGroup === 'localization')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Localization Settings</h2>
                                <p class="text-xs text-gray-400">Default language, timezone, currency and formatting used across the site</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Language</label>
                                    <select wire:model="default_language_id"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @foreach ($languages as $lang)
                                            <option value="{{ $lang->id }}">{{ $lang->name }} ({{ $lang->native_name }})</option>
                                        @endforeach
                                    </select>
                                    @error('default_language_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                    <p class="text-xs text-gray-400 mt-1.5">
                                        Manage available languages under
                                        <a href="{{ route('admin.settings.languages') }}" class="text-indigo-600 hover:underline">Languages</a>.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Timezone</label>
                                    <select wire:model="timezone"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        @foreach ($timezoneOptions as $tz)
                                            <option value="{{ $tz }}">{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Formatting</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Format</label>
                                        <input wire:model="date_format" type="text" placeholder="d-m-Y"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Time Format</label>
                                        <input wire:model="time_format" type="text" placeholder="H:i"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Number Format</label>
                                        <input wire:model="number_format" type="text" placeholder="1,234.56"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Currency</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Currency</label>
                                        <select wire:model="currency"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            @foreach ($currencies as $cur)
                                                <option value="{{ $cur->code }}">{{ $cur->code }} — {{ $cur->name }} ({{ $cur->symbol }})</option>
                                            @endforeach
                                        </select>
                                        @error('currency') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                        <p class="text-xs text-gray-400 mt-1.5">
                                            Manage available currencies under
                                            <a href="{{ route('admin.settings.currencies') }}" class="text-indigo-600 hover:underline">Currencies</a>.
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Currency Symbol</label>
                                        <input type="text" value="{{ $currency_symbol }}" disabled
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                                        <p class="text-xs text-gray-400 mt-1.5">Derived from the selected currency.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ MAIL ══════════════════ --}}
                    @if ($activeGroup === 'mail')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Email Settings</h2>
                                <p class="text-xs text-gray-400">Configure the sender name and address for outgoing emails</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">From Name <span class="text-red-500">*</span></label>
                                    <input wire:model="mail_from_name" type="text" placeholder="My App"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('mail_from_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">From Address <span class="text-red-500">*</span></label>
                                    <input wire:model="mail_from_address" type="email" placeholder="no-reply@example.com"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    @error('mail_from_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                                </svg>
                                <p class="text-xs text-blue-700">SMTP/driver configuration is managed via <code class="bg-blue-100 px-1 rounded">.env</code> — only the sender identity is stored here.</p>
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ SOCIAL ══════════════════ --}}
                    @if ($activeGroup === 'social')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-pink-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Social Links</h2>
                                <p class="text-xs text-gray-400">URLs shown in site footer and contact pages</p>
                            </div>
                        </div>

                        <div class="px-6 py-5">
                            @php
                                $socialFields = [
                                    'facebook'       => ['label' => 'Facebook',       'placeholder' => 'https://facebook.com/yourpage',       'color' => 'text-blue-600'],
                                    'facebook_group' => ['label' => 'Facebook Group', 'placeholder' => 'https://facebook.com/groups/yourgroup','color' => 'text-blue-600'],
                                    'twitter'        => ['label' => 'Twitter / X',    'placeholder' => 'https://x.com/yourhandle',            'color' => 'text-gray-900'],
                                    'instagram'      => ['label' => 'Instagram',      'placeholder' => 'https://instagram.com/yourhandle',    'color' => 'text-pink-600'],
                                    'linkedin'       => ['label' => 'LinkedIn',       'placeholder' => 'https://linkedin.com/company/...' ,    'color' => 'text-blue-700'],
                                    'tiktok'         => ['label' => 'TikTok',         'placeholder' => 'https://tiktok.com/@yourhandle',       'color' => 'text-gray-900'],
                                ];
                            @endphp
                            <div class="divide-y divide-gray-100">
                                @foreach ($socialFields as $field => $meta)
                                    <div class="flex items-center gap-4 py-3.5 first:pt-0 last:pb-0">
                                        <span class="w-28 shrink-0 text-sm font-medium text-gray-700">{{ $meta['label'] }}</span>
                                        <input wire:model="{{ $field }}" type="url" placeholder="{{ $meta['placeholder'] }}"
                                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ══════════════════ REGISTRATION ══════════════════ --}}
                    @if ($activeGroup === 'registration')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Registration Settings</h2>
                                <p class="text-xs text-gray-400">Control who can sign up and what restrictions apply</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-2">
                            @php
                                $toggles = [
                                    ['field' => 'allow_registration',   'label' => 'Allow Registration',        'desc' => 'Enable or disable public user sign-up globally',                        'color' => 'emerald'],
                                    ['field' => 'restrict_by_country',  'label' => 'Restrict by Country',       'desc' => 'Only users from allowed countries can register',                         'color' => 'amber'],
                                    ['field' => 'require_email_verify', 'label' => 'Require Email Verification','desc' => 'Users must verify their email address before accessing the application',  'color' => 'blue'],
                                ];
                            @endphp

                            @foreach ($toggles as $t)
                                @php
                                    $on = $this->{$t['field']};
                                    $colors = [
                                        'emerald' => ['bg_on' => 'bg-emerald-500', 'ring' => 'ring-emerald-200', 'badge_on' => 'bg-emerald-100 text-emerald-700', 'badge_off' => 'bg-gray-100 text-gray-500'],
                                        'amber'   => ['bg_on' => 'bg-amber-500',   'ring' => 'ring-amber-200',   'badge_on' => 'bg-amber-100 text-amber-700',    'badge_off' => 'bg-gray-100 text-gray-500'],
                                        'blue'    => ['bg_on' => 'bg-blue-500',    'ring' => 'ring-blue-200',    'badge_on' => 'bg-blue-100 text-blue-700',      'badge_off' => 'bg-gray-100 text-gray-500'],
                                    ];
                                    $c = $colors[$t['color']];
                                @endphp
                                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 px-4 py-3.5
                                    {{ $on ? 'bg-gray-50' : 'bg-white' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex mt-0.5 text-xs font-semibold px-2 py-0.5 rounded-full {{ $on ? $c['badge_on'] : $c['badge_off'] }}">
                                            {{ $on ? 'ON' : 'OFF' }}
                                        </span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-800">{{ $t['label'] }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $t['desc'] }}</p>
                                        </div>
                                    </div>
                                    <button wire:click="$toggle('{{ $t['field'] }}')" type="button"
                                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-all focus:outline-none
                                            {{ $on ? $c['bg_on'] . ' ring-2 ' . $c['ring'] : 'bg-gray-300' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform
                                            {{ $on ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                            @endforeach

                            {{-- Related links --}}
                            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-3">
                                <a href="{{ route('admin.settings.countries') }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
                                    </svg>
                                    Manage Countries →
                                </a>
                                <a href="{{ route('admin.settings.genders') }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                    </svg>
                                    Manage Genders →
                                </a>
                            </div>
                        </div>
                    @endif

                    @if ($activeGroup === 'pricing')
                        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182.553-.44 1.278-.659 2.003-.659.725 0 1.45.22 2.003.659l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Pricing Settings</h2>
                                <p class="text-xs text-gray-400">Control margin-protection rules used when editing prices</p>
                            </div>
                        </div>

                        <div class="px-6 py-5 space-y-4">
                            <div class="max-w-xs">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Minimum Margin Alert (%)</label>
                                <div class="relative">
                                    <input wire:model="min_margin_percent" type="number" step="0.01" min="0" max="100"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 pr-8 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">%</span>
                                </div>
                                @error('min_margin_percent') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                <p class="text-xs text-gray-400 mt-1.5">
                                    If a sale price is edited below this margin over purchase price, an alert is shown before continuing
                                    (e.g. purchase ৳100, minimum margin 15% → alert triggers if sale price drops below ৳115).
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- ── Footer / Save ── --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Changes are saved per section.</p>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H9V3"/>
                            </svg>
                            Save Settings
                        </button>
                    </div>

                </div>
            </form>
        </div>

    </div>
</div>
