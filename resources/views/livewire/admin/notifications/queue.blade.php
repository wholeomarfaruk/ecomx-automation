<div x-data x-init="$store.pageName = { name: 'Notification Configuration', slug: 'notification-configuration' }" class="space-y-6">

    @include('livewire.admin.notifications.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Queue Settings</h2>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-5">
            <div class="flex items-center gap-2">
                <input wire:model="queue_enabled" type="checkbox" id="queue_enabled" class="rounded border-gray-300">
                <label for="queue_enabled" class="text-sm text-gray-700">Queue Enabled</label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Retry Count</label>
                    <input wire:model="retry_count" type="number" min="0" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Retry Delay (seconds)</label>
                    <input wire:model="retry_delay" type="number" min="0" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Timeout (seconds)</label>
                    <input wire:model="timeout" type="number" min="1" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
            <div class="pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Queue Settings
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Failed Notifications</h2>
                <p class="text-xs text-gray-400">{{ $failedJobs->count() }} failed job(s)</p>
            </div>
            @if ($failedJobs->count())
                <button wire:click="retryFailed" wire:confirm="Retry all failed notification jobs?"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Retry All Failed
                </button>
            @endif
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($failedJobs as $job)
                <div class="px-6 py-3">
                    <p class="text-xs text-gray-500 font-mono">UUID: {{ $job->uuid }}</p>
                    <p class="text-xs text-gray-400">Failed at {{ $job->failed_at }}</p>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-400">No failed jobs.</div>
            @endforelse
        </div>
    </div>

</div>
