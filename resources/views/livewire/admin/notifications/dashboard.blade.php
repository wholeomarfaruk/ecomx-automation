<div x-data x-init="$store.pageName = { name: 'Notification Configuration', slug: 'notification-configuration' }" class="space-y-6">

    @include('livewire.admin.notifications.partials.tabs')

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Total Notifications</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $total }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Sent Today</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $sentToday }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Pending</p>
            <p class="text-xl font-semibold text-gray-900 font-mono">{{ $pending }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-400 mb-1">Failed</p>
            <p class="text-xl font-semibold {{ $failed > 0 ? 'text-red-600' : 'text-gray-900' }} font-mono">{{ $failed }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-700">Queue Status</p>
            <p class="text-sm font-semibold text-gray-900 font-mono">{{ $queuePending ?? 'N/A' }} pending jobs</p>
        </div>
    </div>

</div>
