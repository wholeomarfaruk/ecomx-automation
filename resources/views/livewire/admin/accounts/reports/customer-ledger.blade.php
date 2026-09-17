<div x-data x-init="$store.pageName = { name: '{{ $customer->full_name }} — Ledger', slug: 'accounts-customer-ledger' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.show', ['customer_id' => $customer->id]) }}"
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-800">{{ $customer->full_name }}</h1>
                <p class="text-sm text-gray-400">{{ $customer->phone }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400">{{ $currentBalance >= 0 ? 'Receivable (Due)' : 'Credit / Advance Held' }}</p>
            <p class="text-2xl font-semibold {{ $currentBalance > 0.01 ? 'text-red-500' : ($currentBalance < -0.01 ? 'text-emerald-600' : 'text-gray-800') }}">
                ৳ {{ number_format(abs($currentBalance), 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Entry</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Account</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Debit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Credit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lines as $line)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $line->journalEntry->entry_date->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                @if($line->journalEntry->source_type === \App\Models\Order::class)
                                    <a href="{{ route('admin.accounts.reports.order-ledger', ['orderId' => $line->journalEntry->source_id]) }}"
                                        class="font-medium text-indigo-600 hover:text-indigo-700">{{ $line->journalEntry->entry_number }}</a>
                                @else
                                    <p class="font-medium text-gray-800">{{ $line->journalEntry->entry_number }}</p>
                                @endif
                                <p class="text-xs text-gray-400">{{ $line->memo ?? $line->journalEntry->description }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">
                                <span class="text-gray-400 font-mono text-xs">{{ $line->account->code }}</span>
                                {{ $line->account->name }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-600">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                            <td class="px-5 py-3 text-right text-sm text-red-500">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($line->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4">
            {{ $lines->links() }}
        </div>
    </div>
</div>
