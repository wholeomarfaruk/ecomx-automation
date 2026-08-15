<div x-data x-init="$store.pageName = { name: 'Email Configuration', slug: 'email-configuration' }" class="space-y-6">

    @include('livewire.admin.email.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Choose How Emails Are Sent</h2>
            <p class="text-xs text-gray-400">Pick one option — you can change this anytime</p>
        </div>
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <button wire:click="selectCategory('smtp')" type="button"
                class="text-left p-4 rounded-xl border-2 transition-all
                    {{ $category === 'smtp' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                <p class="text-sm font-semibold text-gray-900">SMTP</p>
                <p class="text-xs text-gray-500 mt-1">Use your own mail server — Gmail, Hostinger, cPanel, Zoho, Outlook, etc.</p>
            </button>
            <button wire:click="selectCategory('api')" type="button"
                class="text-left p-4 rounded-xl border-2 transition-all
                    {{ $category === 'api' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                <p class="text-sm font-semibold text-gray-900">API Mail Provider</p>
                <p class="text-xs text-gray-500 mt-1">Send through a dedicated email API service for better deliverability.</p>
            </button>
            <button wire:click="selectCategory('custom')" type="button"
                class="text-left p-4 rounded-xl border-2 transition-all
                    {{ $category === 'custom' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                <p class="text-sm font-semibold text-gray-900">Custom Driver</p>
                <p class="text-xs text-gray-500 mt-1">A developer-installed custom email integration.</p>
            </button>
        </div>

        @if ($category === 'api')
            <div class="px-6 pb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Select a Provider</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($apiVendors as $key => $label)
                        <button wire:click="selectApiVendor('{{ $key }}')" type="button"
                            class="px-3 py-2 rounded-lg text-sm font-medium border transition-all
                                {{ $apiVendor === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="px-6 pb-6">
            <a href="{{ route('admin.settings.advance.email-configuration.settings') }}"
                class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                Continue to Settings
            </a>
        </div>
    </div>

</div>
