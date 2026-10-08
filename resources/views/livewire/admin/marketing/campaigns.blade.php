<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $platformLabel }} Campaigns</h1>
            <p class="text-sm text-gray-500 mt-1">Campaigns are picked up automatically from ad traffic (utm_campaign){{ $platform ? " on {$platformLabel}" : '' }} — credited by last touch.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @include('livewire.admin.marketing.partials.date-range-picker')
            <button wire:click="openCreate" type="button"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shadow-sm whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Campaign
            </button>
        </div>
    </div>

    {{-- Platform tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200">
        @foreach ([
            ['key' => '', 'label' => 'All Campaigns', 'route' => 'admin.marketing.campaigns.index'],
            ['key' => 'meta', 'label' => 'Meta', 'route' => 'admin.marketing.campaigns.meta'],
            ['key' => 'google', 'label' => 'Google', 'route' => 'admin.marketing.campaigns.google'],
            ['key' => 'youtube', 'label' => 'YouTube', 'route' => 'admin.marketing.campaigns.youtube'],
            ['key' => 'tiktok', 'label' => 'TikTok', 'route' => 'admin.marketing.campaigns.tiktok'],
            ['key' => 'other', 'label' => 'Other / UTM', 'route' => 'admin.marketing.campaigns.other'],
        ] as $tab)
            <a href="{{ route($tab['route']) }}"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                    {{ $platform === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Campaign</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Visitors</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Views</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">ATC</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Checkout</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Purchases</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Conv. Rate</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($campaigns as $campaign)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    @php
                                        $dot = match ($campaign['platform']) {
                                            'meta' => 'bg-blue-500',
                                            'google' => 'bg-amber-500',
                                            'youtube' => 'bg-red-600',
                                            'tiktok' => 'bg-gray-900',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $campaign['name'] }}</div>
                                        <div class="text-xs text-gray-400">
                                            {{ $platforms[$campaign['platform']] ?? ucfirst($campaign['platform']) }}
                                            @if ($campaign['name'] !== $campaign['campaign_key'])
                                                · <span class="font-mono">{{ $campaign['campaign_key'] }}</span>
                                            @endif
                                            @if ($campaign['external_campaign_id'] && $campaign['external_campaign_id'] !== $campaign['campaign_key'])
                                                · {{ $campaign['external_campaign_id'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($campaign['visitors']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($campaign['product_views']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($campaign['add_to_cart']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($campaign['checkout']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($campaign['purchases']) }}</td>
                            <td class="px-3 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $campaign['conversion_rate'] >= 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $campaign['conversion_rate'] }}%
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($campaign['revenue'], 2) }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button wire:click="openEdit({{ $campaign['id'] }})" type="button"
                                    class="text-xs font-medium text-gray-500 hover:text-indigo-600 mr-3">Edit</button>
                                @if ($campaign['campaign_key'])
                                    <a href="{{ route('admin.marketing.events.index', ['campaign' => $campaign['campaign_key']]) }}"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap">
                                        View Events
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No campaigns yet</p>
                                <p class="text-xs text-gray-400 mt-0.5 max-w-md mx-auto">
                                    Campaigns appear here automatically once visitors arrive from an ad link carrying
                                    <code class="bg-gray-100 px-1 rounded">utm_campaign</code> (or Google Ads auto-tagging) — or
                                    <button wire:click="openCreate" type="button" class="text-indigo-600 hover:underline">add one manually</button>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add / Edit Campaign Modal --}}
    <div x-cloak x-data="{ open: @entangle('campaignModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">{{ $editingId ? 'Edit Campaign' : 'Add Campaign' }}</h2>
                    <p class="text-xs text-gray-400">Traffic is matched to it by the UTM campaign value</p>
                </div>
                <button @click="open = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="saveCampaign" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Platform <span class="text-red-500">*</span></label>
                    <select wire:model="formPlatform"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach ($platforms as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('formPlatform') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">UTM campaign value <span class="text-red-500">*</span></label>
                    <input wire:model="formKey" type="text" placeholder="e.g. eid_offer_2026" @disabled($editingId)
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500">
                    <p class="text-[11px] text-gray-400 mt-1">Exactly what the ad link sends as <code class="bg-gray-100 px-1 rounded">utm_campaign=</code></p>
                    @error('formKey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Display name</label>
                    <input wire:model="formName" type="text" placeholder="e.g. Eid Offer — Video Ads"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('formName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Platform campaign ID</label>
                    <input wire:model="formExternalId" type="text" placeholder="e.g. 120249004219140789"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('formExternalId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        {{ $editingId ? 'Save Changes' : 'Add Campaign' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
