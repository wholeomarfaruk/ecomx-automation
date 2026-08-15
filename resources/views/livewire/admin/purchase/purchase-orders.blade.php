<div x-data x-init="$store.pageName = { name: 'Purchase Orders', slug: 'purchase-orders' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-3 gap-3 flex-1 max-w-xl">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Orders</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Pending</p>
                <p class="text-xl font-semibold text-amber-500 mt-0.5">{{ $pendingCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Received</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $receivedCount }}</p>
            </div>
        </div>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Purchase Order
        </button>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order # or supplier…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select wire:model.live="filterSupplier"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Supplier</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        @php
                            $statusStyles = [
                                'pending'   => 'bg-amber-50 text-amber-600',
                                'received'  => 'bg-emerald-50 text-emerald-600',
                                'cancelled' => 'bg-red-50 text-red-500',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800 font-mono">{{ $order->order_number }}</span>
                                @if($order->deadline)
                                    <span class="block text-xs text-gray-400">Due {{ $order->deadline->format('d M, Y') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.purchase.suppliers.ledger', $order->supplier_id) }}" wire:navigate class="text-sm text-gray-600 hover:text-indigo-600 hover:underline">
                                    {{ $order->supplier->name }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $order->variant->product->name }}</span>
                                <span class="block text-xs font-mono text-gray-400">{{ $order->variant->sku }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm text-gray-700">{{ rtrim(rtrim(number_format($order->quantity, 3), '0'), '.') }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ $order->total_amount ? number_format($order->total_amount, 2) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$order->status] ?? 'bg-gray-100 text-gray-500' }}">
                                        {{ $order->status }}
                                    </span>
                                    @if($order->status === 'pending')
                                        <div x-data="{ open: false }" class="relative">
                                            <button @click="open = !open" type="button" class="text-gray-400 hover:text-gray-600 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                                                class="absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-lg shadow-lg z-10 py-1">
                                                <button wire:click="setStatus({{ $order->id }}, 'received')" type="button"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Mark Received</button>
                                                <button wire:click="setStatus({{ $order->id }}, 'cancelled')" type="button"
                                                    class="w-full text-left px-3 py-1.5 text-xs text-red-500 hover:bg-red-50">Cancel Order</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="editOrder({{ $order->id }})" type="button"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                    <button type="button" x-data
                                        @click="Swal.fire({
                                            title: 'Delete purchase order?',
                                            text: '{{ $order->order_number }} will be removed permanently.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#ef4444',
                                            confirmButtonText: 'Delete'
                                        }).then(r => { if (r.isConfirmed) $wire.deleteOrder({{ $order->id }}) })"
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
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No purchase orders found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Try adjusting filters or create a new order</p>
                                    </div>
                                    <button wire:click="openCreateModal"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                        New Purchase Order
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    <div x-cloak x-data="{ open: @entangle('formModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">

            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">{{ $editingId ? 'Edit Purchase Order' : 'New Purchase Order' }}</h2>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Number <span class="text-red-500">*</span></label>
                        <input wire:model="orderNumber" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('orderNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Supplier <span class="text-red-500">*</span></label>
                        <select wire:model="supplierId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Product Variant <span class="text-red-500">*</span></label>
                    @if($variantLabel)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2.5 bg-gray-50/60">
                            <span class="text-sm text-gray-700">{{ $variantLabel }}</span>
                            <button wire:click="$set('variantLabel', '')" type="button" class="text-gray-400 hover:text-red-500 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        <input wire:model.live.debounce.300ms="variantSearch" type="text" placeholder="Search product by name or SKU…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @if($variantSearch !== '')
                            <div class="mt-2 border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-40 overflow-y-auto">
                                @forelse($variantOptions as $variant)
                                    <button wire:click="selectVariant({{ $variant->id }})" type="button"
                                        class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition text-left">
                                        <div>
                                            <span class="text-sm text-gray-800">{{ $variant->product->name }}</span>
                                            <span class="block text-xs font-mono text-gray-400">{{ $variant->sku }}</span>
                                        </div>
                                    </button>
                                @empty
                                    <p class="px-3 py-2 text-xs text-gray-400">No matching variants.</p>
                                @endforelse
                            </div>
                        @endif
                    @endif
                    @error('variantId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Quantity <span class="text-red-500">*</span></label>
                        <input wire:model.live="quantity" type="number" step="0.001" min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('quantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Unit Price</label>
                        <input wire:model.live="unitPrice" type="number" step="0.01" min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('unitPrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if($quantity !== '' && $unitPrice !== '')
                    <div class="flex items-center justify-between rounded-lg bg-indigo-50 px-3 py-2.5">
                        <span class="text-xs text-indigo-600">Total Amount</span>
                        <span class="text-sm font-semibold text-indigo-700">{{ number_format($this->totalAmount, 2) }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Order Date</label>
                        <input wire:model="orderDate" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Deadline</label>
                        <input wire:model="deadline" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-100 shrink-0">
                <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveOrder" type="button" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    {{ $editingId ? 'Save Changes' : 'Create Order' }}
                </button>
            </div>
        </div>
    </div>

</div>
