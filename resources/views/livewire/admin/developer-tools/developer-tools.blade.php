<div x-data x-init="$store.pageName = { name: 'Developer Tools', slug: 'developer-tools' }" class="space-y-6">

    {{-- ══════════════════ PHP INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">PHP Info</h2>
                <p class="text-xs text-gray-400">Key php.ini directives for the current runtime</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($phpInfo as $label => $value)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
                        <input type="text" value="{{ $value }}" disabled
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══════════════════ LARAVEL INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Laravel Info</h2>
                <p class="text-xs text-gray-400">Framework and application configuration</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($laravelInfo as $label => $value)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }}</label>
                        <input type="text" value="{{ $value }}" disabled
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══════════════════ SERVER INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Server Information</h2>
                <p class="text-xs text-gray-400">Host operating system and web server</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($serverInfo as $label => $value)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ str($label)->headline() }}</label>
                        <input type="text" value="{{ $value }}" disabled
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══════════════════ DATABASE INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Database Information</h2>
                <p class="text-xs text-gray-400">Active connection and status</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver</label>
                    <input type="text" value="{{ $databaseInfo['driver'] }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Host</label>
                    <input type="text" value="{{ $databaseInfo['host'] ?? 'N/A' }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Database</label>
                    <input type="text" value="{{ $databaseInfo['database'] ?? 'N/A' }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <div class="px-3 py-2.5">
                        <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $databaseInfo['connected'] ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                            {{ $databaseInfo['connected'] ? 'Connected' : 'Failed' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ QUEUE INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Queue Information</h2>
                <p class="text-xs text-gray-400">Default queue connection and job counts</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver</label>
                    <input type="text" value="{{ $queueInfo['driver'] }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pending Jobs</label>
                    <input type="text" value="{{ $queueInfo['pending'] ?? 'N/A' }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Failed Jobs</label>
                    <input type="text" value="{{ $queueInfo['failed'] ?? 'N/A' }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ CACHE INFO ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V6.75m-8.25 0h16.5v-.75a2.25 2.25 0 00-2.25-2.25H6a2.25 2.25 0 00-2.25 2.25v.75z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Cache Information</h2>
                <p class="text-xs text-gray-400">Default cache store and connectivity</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Store</label>
                    <input type="text" value="{{ $cacheInfo['store'] }}" disabled
                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <div class="px-3 py-2.5">
                        <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $cacheInfo['working'] ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                            {{ $cacheInfo['working'] ? 'Working' : 'Failed' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ COMPOSER VERSION ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-sky-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Composer Version</h2>
                <p class="text-xs text-gray-400">Version of the Composer binary on this host</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <input type="text" value="{{ $composerVersion }}" disabled
                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
        </div>
    </div>

    {{-- ══════════════════ PHP EXTENSIONS ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">PHP Extensions</h2>
                <p class="text-xs text-gray-400">{{ count($phpExtensions) }} extensions loaded</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="max-h-96 overflow-y-auto flex flex-wrap gap-2">
                @foreach ($phpExtensions as $extension)
                    <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-mono">
                        {{ $extension }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══════════════════ INSTALLED PACKAGES ══════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Installed Packages</h2>
                <p class="text-xs text-gray-400">{{ count($installedPackages) }} packages from composer.lock</p>
            </div>
        </div>
        <div class="px-6 py-5">
            <div class="max-h-96 overflow-y-auto border border-gray-100 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Package</th>
                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Version</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($installedPackages as $package)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 font-mono">{{ $package['name'] }}</td>
                                <td class="px-4 py-2 text-gray-500 font-mono">{{ $package['version'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
