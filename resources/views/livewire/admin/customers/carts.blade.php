<div x-data x-init="$store.pageName = { name: 'Carts', slug: 'customers-carts' }">

    {{-- Header --}}
    <div class="grid grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Carts</p>
            <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Active</p>
            <p class="text-xl font-semibold text-indigo-600 mt-0.5">{{ $activeCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Converted</p>
            <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $convertedCount }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Abandoned</p>
            <p class="text-xl font-semibold text-red-500 mt-0.5">{{ $abandonedCount }}</p>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by customer name or phone…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="converted">Converted</option>
                <option value="abandoned">Abandoned</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cart</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Subtotal</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($carts as $cart)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.customers.carts.show', $cart->id) }}'">
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">#{{ $cart->id }}</span>
                                <span class="block text-xs text-gray-400 mt-1">{{ $cart->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-sm font-medium text-gray-800">{{ $cart->customer?->full_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-gray-400">{{ $cart->customer?->phone ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $cart->device?->fingerprint ? Str::limit($cart->device->fingerprint, 16) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ $cart->items_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($cart->subtotal, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $statusStyles = [
                                        'active'    => 'bg-indigo-50 text-indigo-600',
                                        'converted' => 'bg-emerald-50 text-emerald-600',
                                        'abandoned' => 'bg-red-50 text-red-500',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$cart->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $cart->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No carts found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Carts will appear here once customers start shopping</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($carts->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $carts->links() }}
            </div>
        @endif
    </div>
</div>
