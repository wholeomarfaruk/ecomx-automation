<div class="space-y-6" wire:key="marketing-dashboard">

    {{-- Header + range filter --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Marketing Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Live view of tracked visitors, funnel performance, and campaign revenue.</p>
        </div>

        @include('livewire.admin.marketing.partials.date-range-picker')
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['label' => 'Unique Visitors', 'value' => number_format($kpis['visitors']), 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0ZM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632Z', 'accent' => 'text-indigo-600 bg-indigo-50'],
                ['label' => 'Sessions', 'value' => number_format($kpis['sessions']), 'icon' => 'M8.25 4.5l7.5 7.5-7.5 7.5', 'accent' => 'text-sky-600 bg-sky-50'],
                ['label' => 'Identified Customers', 'value' => number_format($kpis['customers']), 'icon' => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0Z', 'accent' => 'text-emerald-600 bg-emerald-50'],
                ['label' => 'Purchases', 'value' => number_format($kpis['purchases']), 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 00-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437', 'accent' => 'text-amber-600 bg-amber-50'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</span>
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $card['accent'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                        </svg>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Revenue + AOV strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-linear-to-br from-indigo-50 to-white border border-indigo-100 rounded-2xl shadow-sm p-5">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Revenue (tracked purchases)</span>
            <div class="text-3xl font-bold text-gray-900 mt-1">৳{{ number_format($kpis['revenue'], 2) }}</div>
        </div>
        <div class="bg-linear-to-br from-emerald-50 to-white border border-emerald-100 rounded-2xl shadow-sm p-5">
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">Average Order Value</span>
            <div class="text-3xl font-bold text-gray-900 mt-1">৳{{ number_format($kpis['aov'], 2) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Trend chart --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Traffic &amp; Purchases Trend</h2>
            <div class="h-64" wire:ignore
                x-data="marketingTrendChart(@js($trend))"
                x-init="init()">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Funnel --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Conversion Funnel</h2>
            <div class="space-y-3">
                @php $maxCount = max(1, $funnel->max('count')); @endphp
                @foreach ($funnel as $stage)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-500">{{ $stage['name'] }}</span>
                            <span class="text-gray-900 font-semibold">{{ number_format($stage['count']) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500"
                                style="width: {{ $stage['count'] > 0 ? max(4, round($stage['count'] / $maxCount * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Campaign table --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Campaign Performance</h2>
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                            <th class="px-5 py-2.5 font-semibold">Campaign</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Visitors</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Views</th>
                            <th class="px-3 py-2.5 font-semibold text-right">ATC</th>
                            <th class="px-3 py-2.5 font-semibold text-right">Purchases</th>
                            <th class="px-5 py-2.5 font-semibold text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($campaigns as $campaign)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        <div>
                                            <div class="text-gray-900 font-medium">{{ $campaign['name'] ?? '—' }}</div>
                                            <div class="text-xs text-gray-400">{{ ucfirst($campaign['platform'] ?? 'unknown') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($campaign['visitors']) }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($campaign['product_views']) }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($campaign['add_to_cart']) }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($campaign['purchases']) }}</td>
                                <td class="px-5 py-3 text-right text-gray-900 font-semibold">৳{{ number_format($campaign['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-gray-400 text-sm">
                                    No campaigns registered yet — see <a href="{{ route('admin.marketing.settings.index') }}" class="text-indigo-600 hover:underline">Tracking Settings</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sources + destination health --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Top Sources</h2>
                <div class="space-y-3">
                    @forelse ($sources as $source)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 capitalize">{{ $source->utm_source }}</span>
                            <span class="text-gray-900 font-semibold">{{ number_format($source->visitors) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No tagged traffic in this range.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Destination Delivery Health</h2>
                <div class="space-y-4">
                    @forelse ($destinationHealth as $destination => $statuses)
                        @php $total = $statuses->sum('total'); @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="text-gray-700 capitalize">{{ $destination }}</span>
                                <span class="text-gray-400">{{ number_format($total) }} events</span>
                            </div>
                            <div class="flex h-2 rounded-full overflow-hidden bg-gray-100">
                                @foreach ($statuses as $row)
                                    @php
                                        $color = match ($row->status?->value ?? $row->status) {
                                            'success' => 'bg-emerald-500',
                                            'failed' => 'bg-red-500',
                                            'retrying' => 'bg-amber-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <div class="{{ $color }}" style="width: {{ round($row->total / max(1, $total) * 100) }}%"></div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No server-side deliveries yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('marketingTrendChart', (trend) => ({
        init() {
            new window.Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Page Views',
                            data: trend.pageViews,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                        {
                            label: 'Purchases',
                            data: trend.purchases,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            borderWidth: 2,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            labels: { color: '#6b7280', usePointStyle: true, boxWidth: 6 },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#9ca3af', maxTicksLimit: 8 },
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { color: '#9ca3af' },
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                            ticks: { color: '#9ca3af' },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
