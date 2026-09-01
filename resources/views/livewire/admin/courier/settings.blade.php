<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">General Settings</h2>
            <p class="text-xs text-gray-400">Applies across every courier</p>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-5">
            <label class="flex items-start gap-3 cursor-pointer">
                <input wire:model="queue_shipment_creation" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Queue shipment creation</span>
                    <span class="block text-xs text-gray-400">Book shipments via the queue instead of blocking the request — recommended so a slow courier API never freezes the admin UI.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input wire:model="webhook_enabled" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Accept courier webhooks</span>
                    <span class="block text-xs text-gray-400">Let couriers push status updates instantly instead of relying solely on the polling sync job.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input wire:model="auto_sync_enabled" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Automatic tracking sync</span>
                    <span class="block text-xs text-gray-400">Poll every non-final shipment for its latest status every 15 minutes (courier:sync-tracking scheduled command).</span>
                </span>
            </label>

            <div class="pt-2">
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Webhook URLs</h2>
            <p class="text-xs text-gray-400">Register these in each courier's merchant panel to enable instant status updates</p>
        </div>
        <div class="px-6 py-5 space-y-3">
            @foreach (\App\Models\Courier::orderBy('sort_order')->get() as $courier)
                <div class="flex items-center justify-between gap-3 p-3 rounded-lg bg-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $courier->name }}</p>
                        <p class="text-xs font-mono text-gray-500">{{ $webhookBaseUrl }}/{{ $courier->slug }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
