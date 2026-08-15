<div x-data x-init="$store.pageName = { name: 'Panel Management', slug: 'panels' }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <p class="text-xs text-gray-500">Panels are managed by developers via <code class="bg-white border border-gray-200 rounded px-1 font-mono">PanelSeeder</code></p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Panels</p>
            <p class="mt-1 text-3xl font-bold text-gray-800">{{ $panels->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Active</p>
            <p class="mt-1 text-3xl font-bold text-green-600">{{ $panels->where('is_active', true)->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Inactive</p>
            <p class="mt-1 text-3xl font-bold text-gray-400">{{ $panels->where('is_active', false)->count() }}</p>
        </div>
    </div>

    {{-- Panel Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse ($panels as $panel)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden {{ ! $panel->is_active ? 'opacity-60' : '' }}">

                {{-- Card Header --}}
                <div class="flex items-start justify-between p-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        @php
                            $iconBg = match($panel->slug) {
                                'admin'   => 'bg-gray-900 text-white',
                                'user'    => 'bg-blue-600 text-white',
                                'website' => 'bg-emerald-500 text-white',
                                default   => 'bg-gray-500 text-white',
                            };
                        @endphp
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl shrink-0 {{ $iconBg }}">
                            @if ($panel->slug === 'admin')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            @elseif ($panel->slug === 'website')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M3 12c0 .778.099 1.533.284 2.253" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $panel->name }}</h3>
                            <code class="text-xs text-gray-400 font-mono">{{ $panel->slug }}</code>
                        </div>
                    </div>

                    @can('panel.edit')
                        <button wire:click="toggleActive({{ $panel->id }})"
                            title="{{ $panel->is_active ? 'Deactivate' : 'Activate' }}"
                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg transition-colors
                                {{ $panel->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" />
                            </svg>
                        </button>
                    @endcan
                </div>

                {{-- Description --}}
                @if ($panel->description)
                    <p class="px-5 pt-4 text-xs text-gray-500">{{ $panel->description }}</p>
                @endif

                {{-- URL --}}
                <div class="px-5 pt-3">
                    @if ($panel->resolved_url)
                        <a href="{{ $panel->resolved_url }}" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors font-mono break-all">
                            {{ $panel->resolved_url }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    @else
                        <span class="text-xs text-gray-400 italic">No route configured</span>
                    @endif
                </div>

                {{-- Users (not shown for website panel) --}}
                @if ($panel->slug !== 'website')
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Users
                                <span class="ml-1 font-bold text-gray-800">{{ $panel->users_count }}</span>
                            </p>
                            <button wire:click="$set('viewingPanelId', {{ $viewingPanel?->id === $panel->id ? 'null' : $panel->id }})"
                                class="text-xs text-gray-400 hover:text-gray-700 transition-colors">
                                {{ $viewingPanel?->id === $panel->id ? 'Hide' : 'View all' }}
                            </button>
                        </div>

                        @if ($panel->users->isNotEmpty())
                            <div class="flex -space-x-2">
                                @foreach ($panel->users->take(6) as $user)
                                    <img
                                        src="{{ 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=111827&color=ffffff&bold=true&size=64' }}"
                                        alt="{{ $user->name }}"
                                        title="{{ $user->name }}"
                                        class="h-8 w-8 rounded-full border-2 border-white object-cover" />
                                @endforeach
                                @if ($panel->users_count > 6)
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-100 text-xs font-semibold text-gray-600">
                                        +{{ $panel->users_count - 6 }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">No users assigned.</p>
                        @endif
                    </div>
                @else
                    <div class="px-5 py-4">
                        <p class="text-xs text-gray-400 italic">Open to everyone — no login required.</p>
                    </div>
                @endif

                {{-- Status Badge --}}
                <div class="px-5 pb-4">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium
                        {{ $panel->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $panel->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                        {{ $panel->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="md:col-span-3 bg-white rounded-xl border border-gray-200 py-16 text-center">
                <p class="text-sm font-medium text-gray-500">No panels found</p>
                <p class="text-xs text-gray-400 mt-1">Run <code class="bg-gray-100 px-1 rounded font-mono">php artisan db:seed --class=PanelSeeder</code></p>
            </div>
        @endforelse
    </div>

    {{-- Expanded User List --}}
    @if ($viewingPanel && $viewingPanel->slug !== 'website')
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-5">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $viewingPanel->name }} — Users</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $viewingPanel->users_count }} user{{ $viewingPanel->users_count !== 1 ? 's' : '' }} assigned</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="userSearch"
                            placeholder="Search users..."
                            class="w-full sm:w-48 rounded-lg border border-gray-200 bg-gray-50 pl-8 pr-3 py-1.5 text-xs text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:ring-0 focus:outline-none transition" />
                    </div>
                    <button wire:click="$set('viewingPanelId', null)" class="text-xs text-gray-400 hover:text-gray-700 shrink-0">Close</button>
                </div>
            </div>

            @if ($viewingPanel->users->isNotEmpty())
                <div class="divide-y divide-gray-50">
                    @foreach ($viewingPanel->users as $user)
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <img
                                    src="{{ 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=111827&color=ffffff&bold=true&size=64' }}"
                                    alt="{{ $user->name }}"
                                    class="h-8 w-8 rounded-full object-cover border border-gray-200 shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                </div>
                            </div>
                            @can('panel.edit')
                                <button
                                    wire:click="removeUser({{ $viewingPanel->id }}, {{ $user->id }})"
                                    wire:confirm="Remove {{ $user->name }} from this panel?"
                                    class="text-xs text-red-500 hover:text-red-700 transition-colors shrink-0">
                                    Remove
                                </button>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <p class="px-5 py-8 text-center text-sm text-gray-400 italic">
                    {{ $userSearch ? 'No users found matching "' . $userSearch . '".' : 'No users in this panel yet.' }}
                </p>
            @endif
        </div>
    @endif

</div>
