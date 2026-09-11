<div x-data x-init="$store.pageName = { name: 'Cash Flow', slug: 'accounts-cash-flow' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">ক্যাশ ফ্লো (Cash Flow)</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden max-w-2xl">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Account</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Net Change</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Ending Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-800">{{ $row['account']->name }}</td>
                            <td class="px-5 py-3 text-right text-sm {{ $row['netChange'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ number_format($row['netChange'], 2) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-gray-600">{{ number_format($row['endingBalance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 font-semibold">
                        <td class="px-5 py-3 text-sm">Total</td>
                        <td class="px-5 py-3 text-right text-sm {{ $totalNetChange >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($totalNetChange, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
