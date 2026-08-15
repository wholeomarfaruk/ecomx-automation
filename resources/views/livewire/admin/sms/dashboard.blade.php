<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    {{-- ── Status Banner ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-6 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full {{ $activeGateway ? 'bg-amber-500' : 'bg-gray-300' }}"></span>
            <div>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $activeGateway ? ucwords(str_replace('_', ' ', $activeGateway->driver_key)) : 'No Gateway Configured' }}
                </p>
                <p class="text-xs text-gray-400">Last checked {{ $lastCheckedAt?->diffForHumans() ?? 'never' }}</p>
            </div>
        </div>
        <span class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full
            {{ $activeGateway ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $activeGateway ? 'Active' : 'Not Configured' }}
        </span>
    </div>

    {{-- ── Key Metrics ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Balance</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $balance ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Today</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $todayCount }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">This Month</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $monthCount }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Failed</p>
            <p class="text-xl font-semibold {{ $failedCount > 0 ? 'text-red-600' : 'text-gray-900' }} font-mono">{{ $failedCount }}</p>
        </div>
    </div>

    {{-- ── Delivery Success Rate ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-700">Delivery Success Rate</p>
            <p class="text-sm font-semibold text-gray-900">{{ $successRate !== null ? $successRate . '%' : 'No data yet' }}</p>
        </div>
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-amber-500 rounded-full" style="width: {{ $successRate ?? 0 }}%"></div>
        </div>
    </div>

    {{-- ── Test & Diagnose ── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Test &amp; Diagnose</h2>
            <p class="text-xs text-gray-400">Verify the active gateway is working correctly</p>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input wire:model="testNumber" type="text" placeholder="+8801XXXXXXXXX" autocomplete="off"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <button wire:click="sendTestSms" type="button"
                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                    Send Test SMS
                </button>
            </div>
            @error('testNumber') <p class="text-xs text-red-500 -mt-2">{{ $message }}</p> @enderror

            <div class="flex flex-wrap gap-2 pt-1">
                <button wire:click="testConnection" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Test Connection
                </button>
                <button wire:click="checkBalance" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Check Balance
                </button>
                <button wire:click="validateCredentials" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Validate Credentials
                </button>
                <button wire:click="refreshStatus" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Refresh Status
                </button>
                <button wire:click="syncAccountInfo" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Sync Account Info
                </button>
            </div>

            @if ($lastResult)
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $lastResult['action'] }} — Result</p>
                    <pre class="text-xs text-gray-600 bg-gray-50 rounded-lg p-4 overflow-x-auto">{{ json_encode($lastResult['data'], JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>

</div>
