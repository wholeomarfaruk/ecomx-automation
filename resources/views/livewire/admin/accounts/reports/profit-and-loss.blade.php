<div x-data x-init="$store.pageName = { name: 'Profit & Loss', slug: 'accounts-pnl' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">লাভ-ক্ষতি (Profit &amp; Loss)</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-2xl">
        <div class="mb-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">আয় (Income)</p>
            @foreach ($income as $row)
                @if ($row['amount'] != 0)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-600">{{ $row['account']->name }}</span>
                        <span class="text-gray-800">{{ number_format($row['amount'], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="flex justify-between text-sm font-medium py-2 border-t border-gray-100 mt-2">
                <span>Total Income</span>
                <span class="text-emerald-600">{{ number_format($totalIncome, 2) }}</span>
            </div>
        </div>

        <div class="mb-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">খরচ (Expenses)</p>
            @foreach ($expenses as $row)
                @if ($row['amount'] != 0)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-600">{{ $row['account']->name }}</span>
                        <span class="text-gray-800">{{ number_format($row['amount'], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="flex justify-between text-sm font-medium py-2 border-t border-gray-100 mt-2">
                <span>Total Expenses</span>
                <span class="text-red-500">{{ number_format($totalExpenses, 2) }}</span>
            </div>
        </div>

        <div class="flex justify-between text-lg font-semibold py-3 border-t-2 border-gray-200">
            <span>নিট লাভ (Net Profit)</span>
            <span class="{{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ number_format($netProfit, 2) }}</span>
        </div>
    </div>
</div>
