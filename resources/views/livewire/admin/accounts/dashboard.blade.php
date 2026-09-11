<div x-data x-init="$store.pageName = { name: 'Accounts Home', slug: 'accounts-dashboard' }">

    <h1 class="text-lg font-semibold text-gray-800 mb-6">🏠 হোম — আজকের সারাংশ</h1>

    {{-- Today's summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">আজকে আসলো (Today In)</p>
            <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ number_format($todayIn, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">আজকে গেলো (Today Out)</p>
            <p class="text-2xl font-semibold text-red-500 mt-1">{{ number_format($todayOut, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">হাতে (Cash on Hand)</p>
            <p class="text-2xl font-semibold text-gray-800 mt-1">{{ number_format($cashOnHand, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs text-gray-400">লাভ (Profit, running)</p>
            <p class="text-2xl font-semibold {{ $currentProfit >= 0 ? 'text-emerald-600' : 'text-red-500' }} mt-1">{{ number_format($currentProfit, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Receivable / Payable quick view --}}
        <a href="{{ route('admin.accounts.receivables.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="text-xs text-gray-400">📥 আমি পাবো (Receivable)</p>
            <p class="text-xl font-semibold text-emerald-600 mt-1">{{ number_format($receivableTotal, 2) }}</p>
        </a>
        <a href="{{ route('admin.accounts.payables.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="text-xs text-gray-400">📤 আমি দিবো (Payable)</p>
            <p class="text-xl font-semibold text-red-500 mt-1">{{ number_format($payableTotal, 2) }}</p>
        </a>
        <a href="{{ route('admin.accounts.reports.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition">
            <p class="text-xs text-gray-400">📦 স্টকের দাম (Stock Value)</p>
            <p class="text-xl font-semibold text-gray-800 mt-1">{{ number_format($stockValue, 2) }}</p>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Today's transactions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/40 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">আজকের লেনদেন</p>
                <a href="{{ route('admin.accounts.transactions') }}" class="text-xs text-indigo-600 hover:text-indigo-700">সব দেখুন</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($todaysTransactions as $entry)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-800">{{ $entry->description ?? $entry->entry_number }}</p>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->transaction_type->badgeClass() }}">
                                {{ $entry->transaction_type->label() }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-gray-800">{{ number_format($entry->totalDebit(), 2) }}</span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-400">আজকে কোনো লেনদেন হয়নি।</div>
                @endforelse
            </div>
        </div>

        {{-- Alerts --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/40">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">⚠️ সতর্কতা (Alerts)</p>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($overdueReceivables as $invoice)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ $invoice->customer->full_name }} — {{ $invoice->invoice_number }}</span>
                        <span class="text-sm font-medium text-red-500">{{ number_format($invoice->amountDue(), 2) }} due</span>
                    </div>
                @empty
                @endforelse

                @forelse ($overdueLoanSchedules as $schedule)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-700">Loan: {{ $schedule->loan->name }} — overdue installment</span>
                        <span class="text-sm font-medium text-red-500">{{ number_format($schedule->principal_due + $schedule->interest_due, 2) }}</span>
                    </div>
                @empty
                @endforelse

                @forelse ($dueRecurringExpenses as $recurring)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-700">Recurring due: {{ $recurring->name }}</span>
                        <span class="text-sm font-medium text-orange-500">{{ number_format($recurring->amount, 2) }}</span>
                    </div>
                @empty
                @endforelse

                @if ($overdueReceivables->isEmpty() && $overdueLoanSchedules->isEmpty() && $dueRecurringExpenses->isEmpty())
                    <div class="px-5 py-8 text-center text-sm text-gray-400">কোনো সতর্কতা নেই।</div>
                @endif
            </div>
        </div>
    </div>
</div>
