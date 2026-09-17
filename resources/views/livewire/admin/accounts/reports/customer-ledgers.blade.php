<div x-data x-init="$store.pageName = { name: 'Customer Ledgers', slug: 'accounts-customer-ledgers' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">কাস্টমার লেজার (Customer Ledgers)</h1>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name or phone…"
            class="px-3 py-2 text-sm rounded-lg border border-gray-300 w-64 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Customer</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $customer->full_name }}</p>
                                <p class="text-xs text-gray-400">{{ $customer->phone }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium
                                {{ $customer->ledgerBalance > 0.01 ? 'text-red-500' : ($customer->ledgerBalance < -0.01 ? 'text-emerald-600' : 'text-gray-800') }}">
                                {{ number_format(abs($customer->ledgerBalance), 2) }}
                                <span class="block text-[11px] font-normal text-gray-400">
                                    {{ $customer->ledgerBalance > 0.01 ? 'Due' : ($customer->ledgerBalance < -0.01 ? 'Advance' : 'Settled') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.accounts.reports.customer-ledger', ['customerId' => $customer->id]) }}"
                                    class="text-sm text-indigo-600 hover:text-indigo-700">View Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-gray-400">No customer ledger activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $customers->links() }}</div>
    </div>
</div>
