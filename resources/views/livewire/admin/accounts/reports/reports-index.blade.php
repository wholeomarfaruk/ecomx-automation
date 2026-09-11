<div x-data x-init="$store.pageName = { name: 'Reports', slug: 'accounts-reports' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">রিপোর্ট (Reports)</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('admin.accounts.reports.pnl') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">লাভ-ক্ষতি (Profit &amp; Loss)</p>
            <p class="text-xs text-gray-400 mt-1">Income vs expenses over a date range</p>
        </a>
        <a href="{{ route('admin.accounts.reports.balance-sheet') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">ব্যালেন্স শিট (Balance Sheet)</p>
            <p class="text-xs text-gray-400 mt-1">Assets = Liabilities + Equity</p>
        </a>
        <a href="{{ route('admin.accounts.reports.cash-flow') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">ক্যাশ ফ্লো (Cash Flow)</p>
            <p class="text-xs text-gray-400 mt-1">Net cash movement by account</p>
        </a>
        <a href="{{ route('admin.accounts.reports.fees') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">fee/চার্জ রিপোর্ট</p>
            <p class="text-xs text-gray-400 mt-1">Every transfer/gateway/conversion fee in one place</p>
        </a>
        <a href="{{ route('admin.accounts.receivables.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">কাস্টমার ledger</p>
            <p class="text-xs text-gray-400 mt-1">Per-customer outstanding &amp; statements</p>
        </a>
        <a href="{{ route('admin.accounts.payables.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">সাপ্লায়ার ledger</p>
            <p class="text-xs text-gray-400 mt-1">Per-supplier outstanding &amp; statements</p>
        </a>
        <a href="{{ route('admin.accounts.loans.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">ঋণ রিপোর্ট (Loans)</p>
            <p class="text-xs text-gray-400 mt-1">Outstanding principal &amp; interest paid</p>
        </a>
        <a href="{{ route('admin.accounts.fixed-assets.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">সম্পদ ও অবচয় (Assets)</p>
            <p class="text-xs text-gray-400 mt-1">Book value &amp; accumulated depreciation</p>
        </a>
        <a href="{{ route('admin.accounts.owner-equity.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="font-medium text-gray-800">মালিক রিপোর্ট (Owner)</p>
            <p class="text-xs text-gray-400 mt-1">Investment vs withdrawal</p>
        </a>
    </div>
</div>
