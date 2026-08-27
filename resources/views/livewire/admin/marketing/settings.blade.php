<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Tracking Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Destination status, UTM normalization rules, and identity matching rules.</p>
    </div>

    <div class="flex items-center gap-1 border-b border-gray-200">
        @foreach ([
            ['key' => 'destinations', 'label' => 'Destinations'],
            ['key' => 'utm', 'label' => 'UTM Rules'],
            ['key' => 'identity', 'label' => 'Identity Rules'],
        ] as $tab)
            <button type="button" wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                    {{ $activeTab === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Destinations tab --}}
    <div @class(['space-y-6' => true, 'hidden' => $activeTab !== 'destinations'])>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Google Tag Manager</h3>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium {{ $gtmEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $gtmEnabled ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        {{ $gtmEnabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">Container ID: {{ config('marketing.gtm.container_id') ?: 'not set' }}</p>
                <p class="text-xs text-gray-400 mt-3">Configured via <code class="bg-gray-100 px-1 rounded">MARKETING_GTM_ENABLED</code> / <code class="bg-gray-100 px-1 rounded">GTM_CONTAINER_ID</code> in .env.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Meta Conversions API</h3>
                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium {{ $metaConfigured ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $metaConfigured ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        {{ $metaConfigured ? 'Configured' : 'Not configured' }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-2">Pixel ID: {{ config('services.meta.pixel_id') ?: 'not set' }}</p>
                <p class="text-xs text-gray-400 mt-3">Configured via <code class="bg-gray-100 px-1 rounded">META_PIXEL_ID</code> / <code class="bg-gray-100 px-1 rounded">META_ACCESS_TOKEN</code> in .env.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Active Server Destinations</h3>
            <div class="flex flex-wrap gap-2">
                @forelse ($serverDestinations as $destination)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 capitalize">{{ $destination }}</span>
                @empty
                    <p class="text-sm text-gray-400">No server-side destinations enabled — set MARKETING_SERVER_DESTINATIONS in .env.</p>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Session Timeout</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ $sessionTimeoutMinutes }} minutes</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Attribution Lifetime</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ $attributionLifetimeDays }} days</p>
            </div>
        </div>
    </div>

    {{-- UTM Rules tab --}}
    <div @class(['space-y-6' => true, 'hidden' => $activeTab !== 'utm'])>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Add Normalization Rule</h3>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Field</label>
                    <select wire:model="newRuleField" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="utm_source">utm_source</option>
                        <option value="utm_medium">utm_medium</option>
                        <option value="utm_campaign">utm_campaign</option>
                        <option value="utm_term">utm_term</option>
                        <option value="utm_content">utm_content</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Match value</label>
                    <input type="text" wire:model="newRuleMatch" placeholder="e.g. fb" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Normalizes to</label>
                    <input type="text" wire:model="newRuleNormalized" placeholder="e.g. facebook" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button wire:click="addUtmRule" type="button" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Add Rule</button>
            </div>
            @error('newRuleMatch') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Field</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Match</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Normalizes To</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Active</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($utmRules as $rule)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-5 py-3 text-sm text-gray-700 font-mono">{{ $rule->field }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700">{{ $rule->match_value }}</td>
                            <td class="px-3 py-3 text-sm font-medium text-gray-900">{{ $rule->normalized_value }}</td>
                            <td class="px-3 py-3 text-center">
                                <button wire:click="toggleUtmRule({{ $rule->id }})" type="button"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rule->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button wire:click="deleteUtmRule({{ $rule->id }})" type="button" class="text-gray-300 hover:text-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">No UTM rules configured — raw values are used as-is.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Identity Rules tab --}}
    <div @class(['space-y-6' => true, 'hidden' => $activeTab !== 'identity'])>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-1">Anonymous-to-Customer Matching</h3>
            <p class="text-xs text-gray-400 mb-4">Which signals are used to link an anonymous device to an existing customer record.</p>

            <div class="space-y-3">
                @foreach ($identityFields as $field)
                    @php $rule = $identityRules->get($field); @endphp
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-800 capitalize">{{ str_replace('_', ' ', $field) }}</p>
                            <p class="text-xs text-gray-400">
                                {{ match($field) {
                                    'email' => 'Match by customer email address',
                                    'phone' => 'Match by customer phone number',
                                    'device_fingerprint' => 'Match by returning device cookie',
                                } }}
                            </p>
                        </div>
                        <button wire:click="toggleIdentityRule('{{ $field }}')" type="button"
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ ($rule?->is_active ?? true) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ ($rule?->is_active ?? true) ? 'Active' : 'Inactive' }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
