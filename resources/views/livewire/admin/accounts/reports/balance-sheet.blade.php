<div x-data x-init="$store.pageName = { name: 'Balance Sheet', slug: 'accounts-balance-sheet' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">ব্যালেন্স শিট (Balance Sheet)</h1>

    @unless ($balances)
        <div class="bg-red-50 text-red-600 text-sm rounded-xl px-4 py-3 mb-4">
            ⚠️ Assets do not equal Liabilities + Equity — check for a data issue.
        </div>
    @endunless

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">সম্পদ (Assets)</p>
            @foreach ($assets as $row)
                @if ($row['amount'] != 0)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-600">{{ $row['account']->name }}</span>
                        <span class="text-gray-800">{{ number_format($row['amount'], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="flex justify-between text-sm font-semibold py-2 border-t border-gray-100 mt-2">
                <span>Total Assets</span>
                <span>{{ number_format($totalAssets, 2) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">দায় (Liabilities)</p>
            @foreach ($liabilities as $row)
                @if ($row['amount'] != 0)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-gray-600">{{ $row['account']->name }}</span>
                        <span class="text-gray-800">{{ number_format($row['amount'], 2) }}</span>
                    </div>
                @endif
            @endforeach
            <div class="flex justify-between text-sm font-semibold py-2 border-t border-gray-100 mt-2">
                <span>Total Liabilities</span>
                <span>{{ number_format($totalLiabilities, 2) }}</span>
            </div>

            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 mt-6">মালিকানা (Equity)</p>
            @foreach ($equity as $row)
                <div class="flex justify-between text-sm py-1">
                    <span class="text-gray-600">{{ $row['account']->name }}</span>
                    <span class="text-gray-800">{{ number_format($row['amount'], 2) }}</span>
                </div>
            @endforeach
            <div class="flex justify-between text-sm py-1">
                <span class="text-gray-600">জমা লাভ (Current Period)</span>
                <span class="text-gray-800">{{ number_format($currentPeriodProfit, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-semibold py-2 border-t border-gray-100 mt-2">
                <span>Total Equity</span>
                <span>{{ number_format($totalEquity, 2) }}</span>
            </div>
        </div>
    </div>
</div>
