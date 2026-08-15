<div x-data x-init="$store.pageName = { name: 'Notification Configuration', slug: 'notification-configuration' }" class="space-y-6">

    @include('livewire.admin.notifications.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Notification Channels</h2>
            <p class="text-xs text-gray-400">Global on/off switch per channel — independent of the per-event matrix below</p>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-center gap-2">
                    <input wire:model="email_enabled" type="checkbox" id="email_enabled" class="rounded border-gray-300">
                    <label for="email_enabled" class="text-sm text-gray-700">Email</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="sms_enabled" type="checkbox" id="sms_enabled" class="rounded border-gray-300">
                    <label for="sms_enabled" class="text-sm text-gray-700">SMS</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="push_enabled" type="checkbox" id="push_enabled" class="rounded border-gray-300">
                    <label for="push_enabled" class="text-sm text-gray-700">Push</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="browser_enabled" type="checkbox" id="browser_enabled" class="rounded border-gray-300">
                    <label for="browser_enabled" class="text-sm text-gray-700">Browser</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="database_enabled" type="checkbox" id="database_enabled" class="rounded border-gray-300">
                    <label for="database_enabled" class="text-sm text-gray-700">Database</label>
                </div>
            </div>
            <div class="pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Channels
                </button>
            </div>
        </form>
    </div>

</div>
