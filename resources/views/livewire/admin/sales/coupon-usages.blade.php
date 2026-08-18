<div x-data x-init="$store.pageName = { name: 'Coupon Usage History', slug: 'sales-coupons-usages' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.coupons') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Coupon Usage History</h1>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
            <p class="text-xs text-gray-400">Total Discount Given</p>
            <p class="text-xl font-semibold text-red-500 mt-0.5">{{ number_format($totalDiscountGiven, 2) }}</p>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <select wire:model.live="filterCoupon"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Coupons</option>
                @foreach($coupons as $coupon)
                    <option value="{{ $coupon->id }}">{{ $coupon->code }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Coupon</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Used At</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Discount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($usages as $usage)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.sales.coupons.show', $usage->coupon_id) }}" wire:navigate class="text-sm font-mono font-medium text-indigo-600 hover:underline">
                                    {{ $usage->coupon->code ?? '—' }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.sales.orders.show', $usage->order_id) }}" wire:navigate class="text-sm text-gray-700 hover:underline">
                                    #{{ $usage->order_id }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $usage->customer?->full_name ?? 'Guest' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-500">{{ $usage->used_at->format('d M, Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ number_format($usage->discount_amount, 2) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No coupon usages yet</p>
                                <p class="text-xs text-gray-400 mt-0.5">Redemptions will appear here once customers use coupons on orders</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usages->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $usages->links() }}
            </div>
        @endif
    </div>
</div>
