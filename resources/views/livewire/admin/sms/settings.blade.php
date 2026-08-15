<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">SMS Settings</h2>
            <p class="text-xs text-gray-400">Global sending behavior, limits, and defaults</p>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-center gap-2">
                    <input wire:model="enabled" type="checkbox" id="enabled" class="rounded border-gray-300">
                    <label for="enabled" class="text-sm text-gray-700">Enable SMS</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="queue_sending" type="checkbox" id="queue_sending" class="rounded border-gray-300">
                    <label for="queue_sending" class="text-sm text-gray-700">Queue Sending</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="retry_failed" type="checkbox" id="retry_failed" class="rounded border-gray-300">
                    <label for="retry_failed" class="text-sm text-gray-700">Retry Failed SMS</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="debug_mode" type="checkbox" id="debug_mode" class="rounded border-gray-300">
                    <label for="debug_mode" class="text-sm text-gray-700">Debug Mode</label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Sender</label>
                    <input wire:model="default_sender" type="text" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Gateway</label>
                    <select wire:model="default_gateway"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach ($gateways as $gateway)
                            <option value="{{ $gateway['key'] }}">{{ $gateway['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Balance Warning Threshold</label>
                    <input wire:model="balance_warning" type="number" min="0" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Daily Limit</label>
                    <input wire:model="daily_limit" type="number" min="0" placeholder="Unlimited" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Limit</label>
                    <input wire:model="monthly_limit" type="number" min="0" placeholder="Unlimited" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
            <div class="pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

</div>
