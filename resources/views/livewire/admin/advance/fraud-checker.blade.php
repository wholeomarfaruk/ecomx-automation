<div x-data x-init="$store.pageName = { name: 'Fraud Checker', slug: 'fraud-checker' }" class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Settings --}}
        <form wire:submit.prevent="save" class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-gray-900">FraudShield</h2>
                    <p class="text-xs text-gray-400">Courier delivery history and fraud risk by customer phone — <a href="https://fraudshield.bd/api/documentation" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">API docs</a></p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $ready ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $ready ? 'Active' : 'Not active' }}
                </span>
            </div>

            <div class="px-6 py-5 space-y-5">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Enable Fraud Checker</span>
                        <span class="block text-xs text-gray-400">Shows "Fraud Check" on the Orders list and the Order page.</span>
                    </span>
                    <input type="checkbox" wire:model="enabled" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </label>

                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Auto-check on Orders list</span>
                        <span class="block text-xs text-gray-400">After the list loads, unchecked rows are checked one by one in the background. Uses your daily quota.</span>
                    </span>
                    <input type="checkbox" wire:model="autoCheckList" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </label>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">API key</label>
                    <input wire:model="apiKey" type="password" autocomplete="off"
                        placeholder="{{ $maskedKey ? 'Saved: ' . $maskedKey . ' — leave empty to keep' : 'cf_…' }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-[11px] text-gray-400">Stored encrypted. Sent as <span class="font-mono">Authorization: Bearer</span>.</p>
                        @if ($maskedKey)
                            <button type="button" wire:click="removeKey" wire:confirm="Remove the saved API key?" class="text-[11px] font-medium text-red-500 hover:text-red-700">Remove key</button>
                        @endif
                    </div>
                    @error('apiKey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Base URL</label>
                        <input wire:model="baseUrl" type="url" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('baseUrl') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Re-use result for (hours)</label>
                        <input wire:model="cacheHours" type="number" min="0" max="720" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <p class="text-[11px] text-gray-400 mt-1">Saves your daily quota. 0 = always call.</p>
                        @error('cacheHours') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Timeout (seconds)</label>
                        <input wire:model="timeout" type="number" min="5" max="60" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('timeout') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3 border-t border-gray-100 bg-gray-50/60">
                <button type="button" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-60 transition">
                    <span wire:loading.remove wire:target="testConnection">Test connection</span>
                    <span wire:loading wire:target="testConnection">Testing…</span>
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">Save</button>
            </div>
        </form>

        {{-- Usage --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Today's usage</h2>
            @if ($usage)
                @php
                    $limit = (int) ($usage['daily_limit'] ?? 0);
                    $used = (int) ($usage['used_today'] ?? 0);
                    $pct = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
                    $package = $usage['package'] ?? null;
                @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $used }}<span class="text-sm font-normal text-gray-400"> / {{ $limit }}</span></p>
                <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ (int) ($usage['remaining_today'] ?? 0) }} checks left today</p>
                @if (! empty($usage['limit_resets_at']))
                    <p class="text-[11px] text-gray-400">Resets {{ \Illuminate\Support\Carbon::parse($usage['limit_resets_at'])->diffForHumans() }}</p>
                @endif
                <div class="mt-4 pt-4 border-t border-gray-100 text-xs">
                    @if ($package)
                        <p class="font-medium text-gray-800">{{ $package['name'] ?? 'Package' }}</p>
                        @if (! empty($package['expires_at']))
                            <p class="text-gray-400">Expires {{ \Illuminate\Support\Carbon::parse($package['expires_at'])->format('d M, Y') }} · {{ (int) ($package['days_remaining'] ?? 0) }} days left</p>
                        @endif
                    @else
                        <p class="text-gray-400">No active package (free tier).</p>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-400">Click <span class="font-medium text-gray-600">Test connection</span> to load today's limit and package.</p>
            @endif
        </div>
    </div>

    {{-- Manual lookup --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-800 mb-4">Check a number</h2>
        <form wire:submit.prevent="lookup" class="flex flex-col sm:flex-row gap-2 max-w-lg">
            <input wire:model="lookupPhone" type="text" inputmode="tel" placeholder="01XXXXXXXXX"
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            <button type="submit" wire:loading.attr="disabled" wire:target="lookup" @disabled(! $ready)
                class="px-4 py-2.5 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition">
                <span wire:loading.remove wire:target="lookup">Check</span>
                <span wire:loading wire:target="lookup">Checking…</span>
            </button>
        </form>
        @error('lookupPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        @if ($lookupResult)
            <div class="mt-5 max-w-2xl">
                @include('livewire.admin.sales.partials.fraud-result', ['check' => $lookupResult])
            </div>
        @endif
    </div>

    {{-- Recent --}}
    @if ($recentChecks->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">Recent checks</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/40 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="px-6 py-2.5 text-left font-semibold">Phone</th>
                            <th class="px-6 py-2.5 text-left font-semibold">Result</th>
                            <th class="px-6 py-2.5 text-right font-semibold">Delivered / Total</th>
                            <th class="px-6 py-2.5 text-right font-semibold">Success</th>
                            <th class="px-6 py-2.5 text-right font-semibold">Checked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($recentChecks as $row)
                            <tr>
                                <td class="px-6 py-2.5 font-mono text-gray-700">{{ $row->phone }}</td>
                                <td class="px-6 py-2.5"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ring-1 {{ $row->badgeClass() }}">{{ $row->levelLabel() }}</span></td>
                                <td class="px-6 py-2.5 text-right tabular-nums text-gray-600">{{ $row->success_parcel }} / {{ $row->total_parcel }}</td>
                                <td class="px-6 py-2.5 text-right tabular-nums text-gray-800">{{ rtrim(rtrim(number_format($row->success_ratio, 1), '0'), '.') }}%</td>
                                <td class="px-6 py-2.5 text-right text-xs text-gray-400 whitespace-nowrap">{{ local_time($row->checked_at)?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
