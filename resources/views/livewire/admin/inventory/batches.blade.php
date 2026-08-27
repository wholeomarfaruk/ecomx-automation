<div x-data x-init="$store.pageName = { name: 'Batches', slug: 'inventory-batches' }">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.inventory.stock') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Batches</h1>
        </div>
        <a href="{{ route('admin.inventory.stock-in') }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Stock In
        </a>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6 max-w-2xl">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Active Batches</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ number_format($activeCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Expiring in {{ $expiringSoonDays }} Days</p>
            <p class="text-xl font-semibold text-amber-600 mt-0.5">{{ number_format($expiringCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Expired</p>
            <p class="text-xl font-semibold text-red-500 mt-0.5">{{ number_format($expiredCount) }}</p>
        </div>
    </div>

    {{-- View mode tabs --}}
    <div class="flex items-center gap-1 mb-4">
        <button wire:click="$set('viewMode', 'list')" type="button"
            class="px-4 py-2 text-sm font-medium rounded-lg transition
                {{ $viewMode === 'list' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200 hover:text-gray-700' }}">
            Batch Records
        </button>
        <button wire:click="$set('viewMode', 'grouped')" type="button"
            class="px-4 py-2 text-sm font-medium rounded-lg transition
                {{ $viewMode === 'grouped' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200 hover:text-gray-700' }}">
            Batch Wise
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Tabs --}}
        <div class="flex items-center gap-1 px-5 pt-4 border-b border-gray-100">
            @foreach(['all' => 'All', 'active' => 'Active', 'expiring' => 'Expiring Soon', 'expired' => 'Expired', 'depleted' => 'Depleted'] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')" type="button"
                    class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition
                        {{ $filter === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by batch no or product name…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
        @if($viewMode === 'list')
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Batch No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Warehouse</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier / PO</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Quantity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Expiry</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm font-mono text-gray-700">{{ $batch->batch_no }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if($batchImages->get($batch->id))
                                        <img src="{{ $batchImages->get($batch->id) }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-100">
                                    @else
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 shrink-0"></span>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="text-sm font-medium text-gray-800 truncate block">{{ $batch->product->name ?? '—' }}</span>
                                        @if($batch->variant)
                                            <span class="block text-xs font-mono text-gray-400">{{ $batch->variant->sku }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $batch->warehouse->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($batch->purchaseOrder)
                                    <a href="{{ route('admin.purchase.orders.edit', $batch->purchaseOrder->id) }}" wire:navigate class="text-sm text-indigo-600 hover:underline">{{ $batch->purchaseOrder->order_number }}</a>
                                    <span class="block text-xs text-gray-400">{{ $batch->supplier->name ?? $batch->purchaseOrder->supplier->name ?? '—' }}</span>
                                @elseif($batch->supplier)
                                    <span class="text-sm text-gray-600">{{ $batch->supplier->name }}</span>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold text-gray-800">{{ rtrim(rtrim(number_format($batch->quantity, 3), '0'), '.') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($batch->expiry_date)
                                    @php
                                        $isExpired = $batch->expiry_date->isPast();
                                        $isExpiringSoon = ! $isExpired && $batch->expiry_date->lte(now()->addDays($expiringSoonDays));
                                    @endphp
                                    <span class="text-sm {{ $isExpired ? 'text-red-500 font-medium' : ($isExpiringSoon ? 'text-amber-600 font-medium' : 'text-gray-600') }}">
                                        {{ $batch->expiry_date->format('M d, Y') }}
                                    </span>
                                    <span class="block text-xs text-gray-400">{{ $batch->expiry_date->diffForHumans() }}</span>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $statusStyles = [
                                        'active' => 'bg-emerald-50 text-emerald-600',
                                        'expired' => 'bg-red-50 text-red-500',
                                        'depleted' => 'bg-gray-100 text-gray-500',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$batch->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $batch->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No batches found</p>
                                <p class="text-xs text-gray-400 mt-0.5">Batches are created when you stock in with "Track as a Batch" enabled</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Batch No</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Records</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total Quantity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Nearest Expiry</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $group)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm font-mono text-gray-700">{{ $group['batch_no'] }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    @if($batchImages->get($group['batch_no']))
                                        <img src="{{ $batchImages->get($group['batch_no']) }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-100">
                                    @else
                                        <span class="w-8 h-8 rounded-lg bg-gray-100 shrink-0"></span>
                                    @endif
                                    <div class="min-w-0">
                                        <span class="text-sm text-gray-700 truncate block">{{ $group['products']->pluck('name')->take(2)->implode(', ') }}</span>
                                        @if($group['products']->count() > 2)
                                            <span class="text-xs text-gray-400"> +{{ $group['products']->count() - 2 }} more</span>
                                        @endif
                                        @if($group['variant_count'] > 0)
                                            <span class="block text-xs text-gray-400">{{ $group['variant_count'] }} variant(s)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ $group['record_count'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-semibold text-gray-800">{{ rtrim(rtrim(number_format($group['total_quantity'], 3), '0'), '.') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($group['nearest_expiry'])
                                    <span class="text-sm {{ $group['has_expired'] ? 'text-red-500 font-medium' : 'text-gray-600' }}">
                                        {{ $group['nearest_expiry']->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $statusStyles = [
                                        'active' => 'bg-emerald-50 text-emerald-600',
                                        'expired' => 'bg-red-50 text-red-500',
                                        'depleted' => 'bg-gray-100 text-gray-500',
                                    ];
                                @endphp
                                @foreach($group['statuses'] as $status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$status] ?? 'bg-gray-100 text-gray-500' }}">
                                        {{ $status }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button wire:click="viewBatch('{{ $group['batch_no'] }}')" type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No batches found</p>
                                <p class="text-xs text-gray-400 mt-0.5">Batches are created when you stock in with "Track as a Batch" enabled</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
        </div>

        @if($batches->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $batches->links() }}
            </div>
        @endif
    </div>

    {{-- Batch detail modal --}}
    @if($viewingBatchNo)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50" wire:click.self="closeBatchView">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">Batch Details</h2>
                        <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $viewingBatchNo }}</p>
                    </div>
                    <button wire:click="closeBatchView" type="button" class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/40">
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Warehouse</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier / PO</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Quantity</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Expiry</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($viewingBatchRecords as $batch)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            @if($viewingBatchImages->get($batch->id))
                                                <img src="{{ $viewingBatchImages->get($batch->id) }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-100">
                                            @else
                                                <span class="w-8 h-8 rounded-lg bg-gray-100 shrink-0"></span>
                                            @endif
                                            <div class="min-w-0">
                                                <span class="text-sm font-medium text-gray-800 truncate block">{{ $batch->product->name ?? '—' }}</span>
                                                @if($batch->variant)
                                                    <span class="block text-xs font-mono text-gray-400">{{ $batch->variant->sku }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="text-sm text-gray-500">{{ $batch->warehouse->name ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($batch->purchaseOrder)
                                            <a href="{{ route('admin.purchase.orders.edit', $batch->purchaseOrder->id) }}" wire:navigate class="text-sm text-indigo-600 hover:underline">{{ $batch->purchaseOrder->order_number }}</a>
                                            <span class="block text-xs text-gray-400">{{ $batch->supplier->name ?? $batch->purchaseOrder->supplier->name ?? '—' }}</span>
                                        @elseif($batch->supplier)
                                            <span class="text-sm text-gray-600">{{ $batch->supplier->name }}</span>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-800">{{ rtrim(rtrim(number_format($batch->quantity, 3), '0'), '.') }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if($batch->expiry_date)
                                            <span class="text-sm {{ $batch->expiry_date->isPast() ? 'text-red-500 font-medium' : 'text-gray-600' }}">
                                                {{ $batch->expiry_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        @php
                                            $statusStyles = [
                                                'active' => 'bg-emerald-50 text-emerald-600',
                                                'expired' => 'bg-red-50 text-red-500',
                                                'depleted' => 'bg-gray-100 text-gray-500',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$batch->status] ?? 'bg-gray-100 text-gray-500' }}">
                                            {{ $batch->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>
