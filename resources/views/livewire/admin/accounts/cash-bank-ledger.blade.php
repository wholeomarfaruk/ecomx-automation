<div x-data x-init="$store.pageName = { name: '{{ $account->name }} — Statement', slug: 'accounts-cash-bank-ledger' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-lg font-semibold text-gray-800">{{ $account->name }}</h1>
            <p class="text-sm text-gray-400">{{ $account->code }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400">Current Balance</p>
            <p class="text-2xl font-semibold text-gray-800">{{ $account->currency?->symbol ?? '৳' }} {{ number_format($currentBalance, 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Entry</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Debit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Credit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lines as $line)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $line->journalEntry->entry_date->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $line->journalEntry->entry_number }}</p>
                                <p class="text-xs text-gray-400">{{ $line->memo ?? $line->journalEntry->description }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-600">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                            <td class="px-5 py-3 text-right text-sm text-red-500">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($line->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No activity yet.</td>
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
