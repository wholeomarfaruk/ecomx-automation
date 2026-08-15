<div x-data x-init="$store.pageName = { name: 'License Configuration', slug: 'license-configuration' }" class="space-y-6">

    @php
        $badge = match ($license->status) {
            'active' => 'bg-amber-100 text-amber-700',
            'expired', 'invalid', 'suspended' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-500',
        };
    @endphp

    @if (! $licenseEnforced)
        {{-- ══════════════════ DEVELOPMENT MODE ══════════════════ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
            <div class="w-12 h-12 mx-auto rounded-xl bg-slate-100 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-gray-900">Development Mode</h2>
            <p class="text-xs text-gray-400 mt-1">License validation and the automatic update system are disabled outside production.</p>
        </div>
    @else
        @if ($license->status === 'unactivated')
            {{-- ══════════════════ ACTIVATION FORM ══════════════════ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Activate License</h2>
                        <p class="text-xs text-gray-400">Enter your license key to activate this installation</p>
                    </div>
                </div>
                <form wire:submit.prevent="activate" class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">License Key</label>
                            <input wire:model="license_key" type="text" placeholder="XXXX-XXXX-XXXX-XXXX"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('license_key') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Registered Domain</label>
                            <input wire:model="registered_domain" type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('registered_domain') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Registered Company</label>
                            <input wire:model="registered_company" type="text" placeholder="Your company name"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('registered_company') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                            Activate License
                        </button>
                    </div>
                </form>
            </div>
        @else
            {{-- ══════════════════ LICENSE DETAILS ══════════════════ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">License Details</h2>
                            <p class="text-xs text-gray-400">Current activation for this installation</p>
                        </div>
                    </div>
                    <button wire:click="recheck" type="button"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                        Re-check
                    </button>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">License Key</label>
                            <input type="text" value="{{ \Illuminate\Support\Str::mask($license->license_key ?? '', '•', 4) }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">License Status</label>
                            <div class="px-3 py-2.5">
                                <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge }}">
                                    {{ ucfirst($license->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan</label>
                            <input type="text" value="{{ $license->plan_name ?? 'N/A' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Activation Date</label>
                            <input type="text" value="{{ $license->activated_at?->format('Y-m-d H:i') ?? 'N/A' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Expiry Date</label>
                            <input type="text" value="{{ $license->expires_at?->format('Y-m-d H:i') ?? 'N/A' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Registered Domain</label>
                            <input type="text" value="{{ $license->registered_domain ?? 'N/A' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Registered Company</label>
                            <input type="text" value="{{ $license->registered_company ?? 'N/A' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">
                        </div>
                    </div>

                    @if ($license->plan_features)
                        <div class="border-t border-gray-100 mt-5 pt-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Plan Features</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($license->plan_features as $feature => $value)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ str($feature)->headline() }}</label>
                                        <input type="text" value="{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}" disabled
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ══════════════════ UPDATES ══════════════════ --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Updates</h2>
                            <p class="text-xs text-gray-400">Automatic updates run hourly and cannot be disabled</p>
                        </div>
                    </div>
                    <button wire:click="checkForUpdates" type="button"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                        Check for Updates
                    </button>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Version</label>
                            <input type="text" value="v{{ $currentVersion }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Latest Version</label>
                            <input type="text" value="{{ $updateCheckResult['latest_version'] ?? 'Unknown' }}" disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500 font-mono">
                        </div>
                    </div>

                    @if (count($updateLogs))
                        <div class="border-t border-gray-100 mt-5 pt-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Update History</p>
                            <div class="border border-gray-100 rounded-xl overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">From</th>
                                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">To</th>
                                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
                                            <th class="text-left px-4 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Finished</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($updateLogs as $log)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-500 font-mono">{{ $log->from_version }}</td>
                                                <td class="px-4 py-2 text-gray-700 font-mono">{{ $log->to_version }}</td>
                                                <td class="px-4 py-2">
                                                    <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                                                        {{ $log->status === 'success' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-gray-500">{{ $log->finished_at?->diffForHumans() ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

</div>
