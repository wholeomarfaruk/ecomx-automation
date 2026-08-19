<div x-data x-init="$store.pageName = { name: 'Active Now', slug: 'active-list' }">

    {{-- Header --}}
    <div class="flex items-center gap-2 mb-6">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <h1 class="text-lg font-semibold text-gray-900">Active Now</h1>
        <span class="text-xs text-gray-400">— active within the last {{ \App\Support\DeviceActivity::ACTIVE_WINDOW_MINUTES }} minutes</span>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-5 border-b border-gray-200">
        @php
            $tabs = [
                'users'     => ['Users', $userCount],
                'customers' => ['Customers', $customerCount],
                'devices'   => ['Devices', $deviceCount],
                'ips'       => ['IPs', $ipCount],
            ];
        @endphp
        @foreach($tabs as $key => [$label, $count])
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition flex items-center gap-2 {{ $tab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-xs font-semibold {{ $tab === $key ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    {{-- Users tab --}}
    @if($tab === 'users')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="relative flex-1 min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="userSearch" type="text" placeholder="Search by name or email…"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <select wire:model.live="userRole" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">User</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Role</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Active</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $u)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <span class="block text-sm font-medium text-gray-800">{{ $u->name }}</span>
                                    <span class="block text-xs text-gray-400">{{ $u->email }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ \Illuminate\Support\Carbon::parse($u->devices_max_last_active_at)->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.users.show', ['user_id' => $u->id]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No active users right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $users->links() }}</div>@endif
        </div>
    @endif

    {{-- Customers tab --}}
    @if($tab === 'customers')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="relative flex-1 min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="customerSearch" type="text" placeholder="Search by name, code, phone…"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <select wire:model.live="customerGroup" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Groups</option>
                    @foreach($customerGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Group</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Active</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($customers as $c)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <span class="block text-sm font-medium text-gray-800">{{ $c->full_name }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 mt-1">{{ $c->customer_code }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $c->customerGroup?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ \Illuminate\Support\Carbon::parse($c->devices_max_last_active_at)->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.users.show', ['customer_id' => $c->id]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No active customers right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $customers->links() }}</div>@endif
        </div>
    @endif

    {{-- Devices tab --}}
    @if($tab === 'devices')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="relative flex-1 min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="deviceSearch" type="text" placeholder="Search by brand, model, IP…"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <select wire:model.live="deviceType" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Types</option>
                    @foreach($deviceTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Owner</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Active</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($devices as $device)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <span class="block text-sm font-medium text-gray-800">{{ $device->device_brand ?? ucfirst($device->device_type) }} {{ $device->device_model }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 mt-1 capitalize">{{ $device->device_type }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $device->user?->name ?? $device->customer?->full_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500 font-mono">{{ $device->ip_address ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ $device->last_active_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('admin.users.devices.show', $device->id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No active devices right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($devices->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $devices->links() }}</div>@endif
        </div>
    @endif

    {{-- IPs tab --}}
    @if($tab === 'ips')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                <div class="relative flex-1 min-w-[200px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="ipSearch" type="text" placeholder="Search by IP address…"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Owner</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Hits</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Seen</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($ips as $ip)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3 text-sm text-gray-800 font-mono">{{ $ip->ip_address }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $ip->device?->device_brand ?? ucfirst($ip->device?->device_type ?? '—') }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $ip->device?->user?->name ?? $ip->device?->customer?->full_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ $ip->hits }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        {{ $ip->last_seen_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($ip->device)
                                        <a href="{{ route('admin.users.devices.show', $ip->device_id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View Device →</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-500">No active IP addresses right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($ips->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $ips->links() }}</div>@endif
        </div>
    @endif

</div>
