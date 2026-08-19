<div x-data x-init="$store.pageName = { name: 'Manage Users', slug: 'users' }">

    {{-- ── Page Header ── --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <button wire:click="$set('UserModal', true)" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
            </svg>
            Add User
        </button>
    </div>

    {{-- ── Main card ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-5 py-4 border-b border-gray-100 space-y-3">

            {{-- Row 1: search + filters + count --}}
            <div class="flex flex-wrap items-center gap-3">

                {{-- Search --}}
                <div class="relative w-full sm:flex-1 sm:min-w-52">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Search by name or email…"
                        autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 pl-9 pr-4 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                {{-- Role filter --}}
                <select wire:model.live="filterRole"
                    class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 sm:min-w-36">
                    <option value="">All Roles</option>
                    @foreach ($roles as $roleItem)
                        <option value="{{ $roleItem->name }}">{{ ucfirst($roleItem->name) }}</option>
                    @endforeach
                </select>

                {{-- Panel filter --}}
                <select wire:model.live="filterPanel"
                    class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 sm:min-w-36">
                    <option value="">All Panels</option>
                    @foreach ($panels as $panelItem)
                        <option value="{{ $panelItem->id }}">{{ $panelItem->name }}</option>
                    @endforeach
                </select>

                {{-- Verified filter --}}
                <select wire:model.live="filterVerified"
                    class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 sm:min-w-36">
                    <option value="">All Status</option>
                    <option value="verified">Verified</option>
                    <option value="unverified">Unverified</option>
                </select>

                {{-- Active filter --}}
                <select wire:model.live="filterActive"
                    class="w-full sm:w-auto rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-700 sm:min-w-36">
                    <option value="">All Activity</option>
                    <option value="active">Active now</option>
                    <option value="inactive">Offline</option>
                </select>

                <span class="text-sm text-gray-400 sm:ml-auto whitespace-nowrap">
                    {{ $users->count() }} {{ Str::plural('user', $users->count()) }}
                    <span class="text-emerald-500">· {{ $activeCount }} active</span>
                </span>
            </div>

            {{-- Row 2: active filter chips (only shown when a filter is active) --}}
            @php
                $hasFilters = $search || $filterRole || $filterPanel || $filterVerified || $filterActive;
            @endphp
            @if($hasFilters)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-400 font-medium">Active:</span>

                    @if($search)
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-700 rounded-full px-2.5 py-1">
                            Search: "{{ $search }}"
                            <button wire:click="$set('search', '')" class="text-gray-400 hover:text-gray-700 ml-0.5">&times;</button>
                        </span>
                    @endif

                    @if($filterRole)
                        <span class="inline-flex items-center gap-1 text-xs bg-indigo-100 text-indigo-700 rounded-full px-2.5 py-1">
                            Role: {{ ucfirst($filterRole) }}
                            <button wire:click="$set('filterRole', '')" class="text-indigo-400 hover:text-indigo-700 ml-0.5">&times;</button>
                        </span>
                    @endif

                    @if($filterPanel)
                        <span class="inline-flex items-center gap-1 text-xs bg-indigo-100 text-indigo-700 rounded-full px-2.5 py-1">
                            Panel: {{ $panels->firstWhere('id', (int)$filterPanel)?->name ?? $filterPanel }}
                            <button wire:click="$set('filterPanel', '')" class="text-indigo-400 hover:text-indigo-700 ml-0.5">&times;</button>
                        </span>
                    @endif

                    @if($filterVerified)
                        <span class="inline-flex items-center gap-1 text-xs bg-emerald-100 text-emerald-700 rounded-full px-2.5 py-1">
                            {{ ucfirst($filterVerified) }}
                            <button wire:click="$set('filterVerified', '')" class="text-emerald-400 hover:text-emerald-700 ml-0.5">&times;</button>
                        </span>
                    @endif

                    @if($filterActive)
                        <span class="inline-flex items-center gap-1 text-xs bg-emerald-100 text-emerald-700 rounded-full px-2.5 py-1">
                            {{ $filterActive === 'active' ? 'Active now' : 'Offline' }}
                            <button wire:click="$set('filterActive', '')" class="text-emerald-400 hover:text-emerald-700 ml-0.5">&times;</button>
                        </span>
                    @endif

                    <button wire:click="clearFilters"
                        class="text-xs text-red-500 hover:text-red-700 font-medium ml-1 underline underline-offset-2">
                        Clear all
                    </button>
                </div>
            @endif

        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/60">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Panels</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Verified</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Activity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Joined</th>
                        <th class="sticky right-0 bg-gray-50/60 px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    @forelse($users as $userItem)
                        @php
                            $userAvatar = $userItem->avatar_id ? (file_path($userItem->avatar_id) ?? null) : null;
                            $userAvatar ??= 'https://ui-avatars.com/api/?name=' . urlencode($userItem->name) . '&background=6366f1&color=ffffff&bold=true&size=128';
                            $roleName   = $userItem->roles->first()?->name;
                            $roleColors = [
                                'superadmin' => 'bg-purple-100 text-purple-700',
                                'admin'      => 'bg-red-100 text-red-700',
                                'manager'    => 'bg-amber-100 text-amber-700',
                                'user'       => 'bg-blue-100 text-blue-700',
                            ];
                            $roleClass = $roleColors[$roleName] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <tr class="group hover:bg-gray-50/60 transition">

                            {{-- User --}}
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <a @if($userItem->avatar_id) data-fancybox href="{{ $userAvatar }}" @endif
                                       class="shrink-0 block">
                                        <img src="{{ $userAvatar }}" alt="{{ $userItem->name }}"
                                            class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm {{ $userItem->avatar_id ? 'cursor-zoom-in' : '' }}">
                                    </a>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $userItem->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $userItem->email }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Role --}}
                            <td class="px-5 py-3.5">
                                @if($roleName)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleClass }}">
                                        {{ $roleName }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">No role</span>
                                @endif
                            </td>

                            {{-- Panels --}}
                            <td class="px-5 py-3.5">
                                @if($userItem->panels->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($userItem->panels->take(2) as $panel)
                                            <span class="text-[10px] px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded-full font-medium border border-indigo-100">
                                                {{ $panel->name }}
                                            </span>
                                        @endforeach
                                        @if($userItem->panels->count() > 2)
                                            <span class="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full font-medium">
                                                +{{ $userItem->panels->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Verified --}}
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium
                                        {{ $userItem->email_verified_at ? 'text-green-600' : 'text-gray-400' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            @if ($userItem->email_verified_at)
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75l6 4.5m0-4.5-6 4.5"/>
                                            @endif
                                        </svg>
                                        Email
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs font-medium
                                        {{ $userItem->phone_verified_at ? 'text-green-600' : 'text-gray-400' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            @if ($userItem->phone_verified_at)
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75l6 4.5m0-4.5-6 4.5"/>
                                            @endif
                                        </svg>
                                        Phone
                                    </span>
                                </div>
                            </td>

                            {{-- Activity --}}
                            <td class="px-5 py-3.5">
                                @php
                                    $lastActive = $userItem->devices_max_last_active_at
                                        ? \Illuminate\Support\Carbon::parse($userItem->devices_max_last_active_at)
                                        : null;
                                    $isActive = $lastActive && $lastActive->greaterThanOrEqualTo(now()->subMinutes(\App\Support\DeviceActivity::ACTIVE_WINDOW_MINUTES));
                                @endphp
                                @if($isActive)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active now
                                    </span>
                                @elseif($lastActive)
                                    <span class="text-xs text-gray-400">Last seen {{ $lastActive->diffForHumans() }}</span>
                                @else
                                    <span class="text-xs text-gray-300 italic">Never seen</span>
                                @endif
                            </td>

                            {{-- Joined --}}
                            <td class="px-5 py-3.5">
                                <span class="text-sm text-gray-500">{{ local_time($userItem->created_at)?->format('d M Y') }}</span>
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 bg-white group-hover:bg-gray-50/60 px-5 py-3.5">
                                <div x-data="{
                                        open: false,
                                        top: 0,
                                        right: 0,
                                        toggle() {
                                            const r = this.$refs.btn.getBoundingClientRect();
                                            this.top   = r.bottom + window.scrollY + 4;
                                            this.right = window.innerWidth - r.right;
                                            this.open  = !this.open;
                                        }
                                    }"
                                    class="flex justify-end">

                                    <button x-ref="btn" @click="toggle()" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                                        </svg>
                                    </button>

                                    <template x-teleport="body">
                                        <div x-show="open"
                                             @click.outside="open = false"
                                             @keydown.escape.window="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             :style="`position: absolute; top: ${top}px; right: ${right}px; z-index: 9999;`"
                                             class="w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-1 text-sm origin-top-right">

                                            @can('user.view')
                                            <button wire:click="viewUser({{ $userItem->id }})" @click="open = false" type="button"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                </svg>
                                                View / Edit
                                            </button>
                                            <a href="{{ route('admin.users.show', ['user_id' => $userItem->id]) }}"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/>
                                                </svg>
                                                Full Details
                                            </a>
                                            @endcan

                                            <a href="{{ route('admin.activity-log', ['filterCauser' => $userItem->email]) }}"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                                </svg>
                                                View Activity
                                            </a>

                                            <button wire:click="sendPasswordResetLink({{ $userItem->id }})" @click="open = false" type="button"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z"/>
                                                </svg>
                                                Send Reset Link
                                            </button>

                                            @can('user.delete')
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <button type="button"
                                                @click="Swal.fire({
                                                    title: 'Delete user?',
                                                    text: 'This action cannot be undone.',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#ef4444',
                                                    confirmButtonText: 'Yes, delete'
                                                }).then(r => { if (r.isConfirmed) $wire.deleteUser({{ $userItem->id }}); open = false; })"
                                                class="flex items-center gap-2.5 w-full px-4 py-2 text-red-600 hover:bg-red-50 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                                Delete User
                                            </button>
                                            @endcan

                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                        </svg>
                                    </div>
                                    @php $hasActiveFilters = $search || $filterRole || $filterPanel || $filterVerified; @endphp
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">
                                            {{ $hasActiveFilters ? 'No users match your filters' : 'No users yet' }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Add your first user to get started.' }}
                                        </p>
                                    </div>
                                    @if($hasActiveFilters)
                                        <button wire:click="clearFilters"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                            Clear filters
                                        </button>
                                    @else
                                        <button wire:click="$set('UserModal', true)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            Add User
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </div>

    {{-- ── Modals ── --}}
    @include('livewire.admin.users.partials.view-modal')
    @include('livewire.admin.users.partials.create-modal')

</div>
