<div x-data x-init="$store.pageName = { name: 'Fee Report', slug: 'accounts-fee-report' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">fee/চার্জ রিপোর্ট</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden max-w-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fee Type</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($feeAccounts as $row)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-800">{{ $row['account']->name }}</td>
                            <td class="px-5 py-3 text-right text-sm text-gray-800">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 font-semibold">
                        <td class="px-5 py-3 text-sm">Total Fees</td>
                        <td class="px-5 py-3 text-right text-sm text-red-500">{{ number_format($totalFees, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
