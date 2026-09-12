<div x-data x-init="$store.pageName = { name: 'Stock Detail', slug: 'inventory-stock-detail' }">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.inventory.stock') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>

            <div class="w-14 h-14 rounded-xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                @if($thumbnail)
                    <img src="{{ $thumbnail }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                @endif
            </div>

            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h1>
                <p class="text-xs text-gray-400 font-mono">{{ $variant->sku ?? $product->code }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.inventory.stock-in') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Stock In
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $qty = (float) $currentQuantity;
        $statusBadge = $qty <= 0 ? 'bg-red-50 text-red-500' : ($qty <= $reorderLevel ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600');
        $statusLabel = $qty <= 0 ? 'Out of Stock' : ($qty <= $reorderLevel ? 'Low Stock' : 'In Stock');
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Current Stock</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ rtrim(rtrim(number_format($currentQuantity, 3), '0'), '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Status</p>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1 {{ $statusBadge }}">{{ $statusLabel }}</span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total In</p>
            <p class="text-xl font-semibold text-emerald-600 mt-0.5">+{{ rtrim(rtrim(number_format($totalIn, 3), '0'), '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Out</p>
            <p class="text-xl font-semibold text-red-500 mt-0.5">-{{ rtrim(rtrim(number_format($totalOut, 3), '0'), '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Active Batches</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($activeBatchCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Last Movement</p>
            <p class="text-sm font-semibold text-gray-800 mt-1.5">{{ $lastMovement?->created_at?->format('M j, Y') ?? '—' }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Stock Trend</h2>
            @if(count($trend['labels']))
                <div class="h-64" wire:ignore x-data="stockTrendChart(@js($trend))" x-init="init()">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-sm text-gray-400">No movement history yet</div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Movement by Type</h2>
            @if(count($breakdown['labels']))
                <div class="h-64" wire:ignore x-data="stockBreakdownChart(@js($breakdown))" x-init="init()">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-sm text-gray-400">No movement history yet</div>
            @endif
        </div>
    </div>

    {{-- Batch breakdown --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Batch Breakdown</h2>
            <p class="text-xs text-gray-400 mt-0.5">How much of this item's stock sits in each batch/lot</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Batch No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Warehouse</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Mfg. Date</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Expiry</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Purchase Price</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                        @php
                            $expired = $batch->isExpired();
                            $batchStatus = [
                                'active' => 'bg-emerald-50 text-emerald-600',
                                'depleted' => 'bg-gray-100 text-gray-500',
                                'expired' => 'bg-red-50 text-red-500',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm font-mono text-gray-700">{{ $batch->batch_no }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $batch->warehouse->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $batch->supplier->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $expired ? $batchStatus['expired'] : ($batchStatus[$batch->status] ?? 'bg-gray-100 text-gray-500') }}">
                                    {{ $expired ? 'Expired' : $batch->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs text-gray-500">{{ $batch->manufacture_date?->format('M j, Y') ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs {{ $expired ? 'text-red-500 font-medium' : 'text-gray-500' }}">{{ $batch->expiry_date?->format('M j, Y') ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs text-gray-500">{{ $batch->purchase_price !== null ? number_format($batch->purchase_price, 2) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold text-gray-800">{{ rtrim(rtrim(number_format($batch->quantity, 3), '0'), '.') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-700">No batches recorded</p>
                                <p class="text-xs text-gray-400 mt-0.5">Stock for this item hasn't been received against a batch yet</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Movement history --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Movement History</h2>
                <p class="text-xs text-gray-400 mt-0.5">Every stock change logged for this item</p>
            </div>
            <a href="{{ route('admin.inventory.movements') }}" class="text-xs font-medium text-indigo-600 hover:underline">View all movements →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Batch</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Change</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Before → After</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $movement->created_at?->format('M d, Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $typeStyles = [
                                        'initial' => 'bg-gray-100 text-gray-500',
                                        'import' => 'bg-gray-100 text-gray-500',
                                        'sale' => 'bg-red-50 text-red-500',
                                        'sale_cancelled' => 'bg-emerald-50 text-emerald-600',
                                        'return' => 'bg-emerald-50 text-emerald-600',
                                        'purchase' => 'bg-blue-50 text-blue-600',
                                        'adjustment' => 'bg-indigo-50 text-indigo-600',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $typeStyles[$movement->type] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ str_replace('_', ' ', $movement->type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs font-mono text-gray-500">{{ $movement->batch->batch_no ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($movement->quantity, 3), '0'), '.') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-xs text-gray-400">
                                    {{ rtrim(rtrim(number_format($movement->before_quantity, 3), '0'), '.') }}
                                    →
                                    {{ rtrim(rtrim(number_format($movement->after_quantity, 3), '0'), '.') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $movement->createdBy?->name ?? 'System' }}</span>
                                @if($movement->note)
                                    <span class="block text-xs text-gray-400" title="{{ $movement->note }}">{{ \Illuminate\Support\Str::limit($movement->note, 40) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No stock movements yet</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>

@script
<script>
    Alpine.data('stockTrendChart', (trend) => ({
        init() {
            new window.Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Stock Balance',
                            data: trend.balances,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#9ca3af', maxTicksLimit: 8 },
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { color: '#9ca3af' },
                            beginAtZero: true,
                        },
                    },
                },
            });
        },
    }));

    Alpine.data('stockBreakdownChart', (breakdown) => ({
        init() {
            const values = breakdown.values.map(v => Math.abs(v));
            const total = values.reduce((sum, v) => sum + v, 0);
            const pct = (v) => total > 0 ? (v / total * 100).toFixed(1) : '0.0';

            new window.Chart(this.$refs.canvas, {
                type: 'doughnut',
                data: {
                    labels: breakdown.labels,
                    datasets: [
                        {
                            data: values,
                            backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#a855f7'],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#6b7280',
                                usePointStyle: true,
                                boxWidth: 6,
                                padding: 12,
                                font: { size: 11 },
                                generateLabels(chart) {
                                    return chart.data.labels.map((label, i) => ({
                                        text: `${label} (${pct(values[i])}%)`,
                                        fillStyle: chart.data.datasets[0].backgroundColor[i],
                                        strokeStyle: chart.data.datasets[0].backgroundColor[i],
                                        index: i,
                                    }));
                                },
                            },
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return `${context.label}: ${context.raw} (${pct(context.raw)}%)`;
                                },
                            },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
