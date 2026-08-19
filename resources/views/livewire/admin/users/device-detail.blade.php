<div x-data x-init="$store.pageName = { name: 'Device Details', slug: 'device-details' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.devices') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $device->device_brand ?? ucfirst($device->device_type) }} {{ $device->device_model }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ $device->fingerprint }}</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5">
            @if($device->is_trusted)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">Trusted</span>
            @endif
            @if($isBlocked)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-500">Blocked</span>
            @endif
            <button wire:click="openBlockModal" type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Add Block
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center gap-1 px-5 pt-3 border-b border-gray-100">
            <button type="button" wire:click="$set('tab', 'info')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'info' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Device Info
            </button>
            <button type="button" wire:click="$set('tab', 'ips')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'ips' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                IP Addresses
            </button>
            <button type="button" wire:click="$set('tab', 'visits')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'visits' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Visited URLs
            </button>
            <button type="button" wire:click="$set('tab', 'blocks')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $tab === 'blocks' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Blocks
            </button>
        </div>

        @if($tab === 'info')
        {{-- Device Info --}}
        <div class="p-6">
            <dl class="grid grid-cols-2 gap-x-8 gap-y-5">
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Owner</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->user?->name ?? $device->customer?->full_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Device Type</dt>
                    <dd class="mt-1 text-sm text-gray-800 capitalize">{{ $device->device_type }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Brand / Model</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->device_brand ?? '—' }} {{ $device->device_model }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Manufacturer</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->manufacturer ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Operating System</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->operating_system ?? '—' }} {{ $device->os_version }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Browser</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->browser ?? '—' }} {{ $device->browser_version }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Platform</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->platform ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Screen</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->screen_resolution ?? '—' }} <span class="text-gray-400">{{ $device->screen_density }}</span></dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Language</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->language ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Timezone</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->timezone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Last IP Address</dt>
                    <dd class="mt-1 text-sm text-gray-800 font-mono">{{ $device->ip_address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Last Active</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->last_active_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Last Login</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->last_login_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">First Seen</dt>
                    <dd class="mt-1 text-sm text-gray-800">{{ $device->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">User Agent</dt>
                    <dd class="mt-1 text-sm text-gray-600 break-all">{{ $device->user_agent ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Sec-CH-UA</dt>
                    <dd class="mt-1 text-sm text-gray-600 break-all">{{ $device->sec_ch_ua ?? '—' }}</dd>
                </div>
            </dl>
        </div>
        @endif

        @if($tab === 'ips')
        {{-- IP Addresses --}}
        <div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Hits</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">First Seen</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($ipAddresses as $ip)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3 text-sm text-gray-800 font-mono">{{ $ip->ip_address }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ $ip->hits }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $ip->first_seen_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $ip->last_seen_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No IP addresses recorded yet</p>
                                    <p class="text-xs text-gray-400 mt-0.5">IP history is captured automatically as this device browses the site.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($ipAddresses->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $ipAddresses->links() }}
                </div>
            @endif
        </div>
        @endif

        @if($tab === 'visits')
        {{-- Visited URLs --}}
        <div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">URL</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Route</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Visited At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($visits as $visit)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3 text-sm text-gray-800 break-all max-w-xs">{{ $visit->url }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $visit->route_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono {{ $visit->status_code && $visit->status_code < 400 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                                        {{ $visit->status_code ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-500 font-mono">{{ $visit->ip_address ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $visit->created_at?->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No visits recorded yet</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Page visits are captured automatically as this device browses the site.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($visits->hasPages())
                <div class="px-5 py-3 border-t border-gray-100">
                    {{ $visits->links() }}
                </div>
            @endif
        </div>
        @endif

        @if($tab === 'blocks')
        {{-- Blocks --}}
        <div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Scope</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Reason</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Blocked By</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Expires</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($blocks as $block)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $block->scope === 'full_site' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">{{ $block->scopeLabel() }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-gray-600 max-w-xs">{{ $block->reason ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $block->blockedBy?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $block->expires_at?->format('d M Y, h:i A') ?? 'Never' }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if(! $block->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Disabled</span>
                                    @elseif($block->isExpired())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Expired</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600">Active</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="toggleBlock({{ $block->id }})" type="button"
                                            title="{{ $block->is_active ? 'Disable' : 'Re-enable' }}"
                                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-amber-50 hover:text-amber-600 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A3.75 3.75 0 0 1 12 1.5v0a3.75 3.75 0 0 1 3.75 3.75V9m-9 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-6a1.5 1.5 0 0 1 1.5-1.5Z"/>
                                            </svg>
                                        </button>
                                        <button type="button" x-data
                                            @click="Swal.fire({
                                                title: 'Remove block?',
                                                text: 'This block record will be deleted permanently.',
                                                icon: 'warning',
                                                showCancelButton: true,
                                                confirmButtonColor: '#ef4444',
                                                confirmButtonText: 'Delete'
                                            }).then(r => { if (r.isConfirmed) $wire.deleteBlock({{ $block->id }}) })"
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
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <p class="text-sm font-semibold text-gray-700">No blocks on this device</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Use "Add Block" above to restrict this device.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Add Block Modal --}}
    <div x-cloak x-data="{ open: @entangle('blockModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <div class="flex-1"><h2 class="text-base font-semibold text-gray-900">Block This Device</h2></div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="createBlock" class="overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Scope <span class="text-red-500">*</span></label>
                    <select wire:model="blockScope" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="full_site">Full Site — hides the entire storefront (404)</option>
                        <option value="orders">Orders — can't view order tracking</option>
                        <option value="checkout">Checkout — can browse/cart but not place orders</option>
                        <option value="account_panel">Account Panel — can't access the admin panel</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason</label>
                    <textarea wire:model="blockReason" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('blockReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Expires At <span class="text-gray-400">(leave empty for permanent)</span></label>
                    <input wire:model="blockExpiresAt" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('blockExpiresAt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Create Block</button>
                </div>
            </form>
        </div>
    </div>

</div>
