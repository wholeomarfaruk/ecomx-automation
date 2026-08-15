<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Choose a Gateway</h2>
            <p class="text-xs text-gray-400">Installed gateways appear here automatically — new ones show up after an update</p>
        </div>
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ($gateways as $gateway)
                <button wire:click="$set('selectedGateway', '{{ $gateway['key'] }}')" type="button"
                    class="text-left p-4 rounded-xl border-2 transition-all
                        {{ $selectedGateway === $gateway['key'] ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $gateway['label'] }}</p>
                        @if ($activeKey === $gateway['key'])
                            <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Active</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500">Key: {{ $gateway['key'] }} &middot; v{{ $gateway['version'] }}</p>
                </button>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Configure {{ $selectedMeta['label'] ?? $selectedGateway }}</h2>
            <p class="text-xs text-gray-400">Save to activate this gateway for sending</p>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-5">

            @if ($selectedMeta)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Credentials</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($selectedMeta['fields'] as $field)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $field['label'] }}</label>
                                <input wire:model="credentials.{{ $field['name'] }}"
                                    type="{{ $field['type'] === 'password' ? 'password' : 'text' }}"
                                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">General Settings</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Sender ID</label>
                        <input wire:model="sender_id" type="text" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Default Country Code</label>
                        <input wire:model="default_country_code" type="text" placeholder="+880" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Timeout (seconds)</label>
                        <input wire:model="timeout" type="number" min="1" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Retry Attempts</label>
                        <input wire:model="retry_attempts" type="number" min="0" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Rate Limit (per minute)</label>
                        <input wire:model="rate_limit_per_minute" type="number" min="0" placeholder="Unlimited" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Configuration
                </button>
            </div>
        </form>
    </div>

</div>
