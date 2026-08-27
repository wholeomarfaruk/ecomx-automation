<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $platformLabel }} Campaigns</h1>
            <p class="text-sm text-gray-500 mt-1">Performance across every registered campaign{{ $platform ? " on {$platformLabel}" : '' }}.</p>
        </div>

        @include('livewire.admin.marketing.partials.date-range-picker')
    </div>

    {{-- Platform tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200">
        @foreach ([
            ['key' => '', 'label' => 'All Campaigns', 'route' => 'admin.marketing.campaigns.index'],
            ['key' => 'meta', 'label' => 'Meta', 'route' => 'admin.marketing.campaigns.meta'],
            ['key' => 'google', 'label' => 'Google', 'route' => 'admin.marketing.campaigns.google'],
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
                                            'tiktok' => 'bg-gray-900',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <span class="w-2 h-2 rounded-full {{ $dot }}"></span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $campaign['name'] }}</div>
                                        <div class="text-xs text-gray-400">
                                            {{ ucfirst($campaign['platform']) }}
                                            @if ($campaign['external_campaign_id'])
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
                            <td class="px-5 py-3 text-right">
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
                                <p class="text-sm font-semibold text-gray-700">No campaigns found</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Register one under <a href="{{ route('admin.marketing.settings.index') }}" class="text-indigo-600 hover:underline">Tracking Settings</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
