<div x-data x-init="$store.pageName = { name: 'Email Configuration', slug: 'email-configuration' }" class="space-y-6">

    @include('livewire.admin.email.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Test Your Email Setup</h2>
            <p class="text-xs text-gray-400">Confirm your provider is configured correctly before relying on it</p>
        </div>
        <div class="px-6 py-5 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Send Test Email To</label>
                <div class="flex gap-3">
                    <input wire:model="testEmail" type="email" placeholder="you@example.com" autocomplete="off"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <button wire:click="sendTestEmail" type="button"
                        class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                        Send Test Email
                    </button>
                </div>
                @error('testEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="border-t border-gray-100 pt-4">
                <button wire:click="testConnection" type="button"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Connection Test
                </button>
                <p class="text-xs text-gray-400 mt-2">Checks that your provider can be reached and authenticated, without sending an email.</p>
            </div>
        </div>
    </div>

    @if ($lastResult)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5">
                <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full
                    {{ $lastResult['success'] ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                    {{ $lastResult['success'] ? 'Success' : 'Failed' }}
                </span>
                <p class="text-sm text-gray-600 mt-2">{{ $lastResult['message'] }}</p>
            </div>
        </div>
    @endif

</div>
