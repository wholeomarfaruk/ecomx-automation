<div x-data x-init="$store.pageName = { name: 'Email Configuration', slug: 'email-configuration' }" class="space-y-6">

    @include('livewire.admin.email.partials.tabs')

    @php
        $mailerLabels = [
            'smtp' => 'SMTP',
            'resend' => 'Resend',
            'mailgun' => 'Mailgun',
            'ses' => 'Amazon SES',
            'postmark' => 'Postmark',
            'custom' => 'Custom Driver',
        ];
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Email Settings</h2>
                <p class="text-xs text-gray-400">Active provider: {{ $mailerLabels[$active_mailer] ?? $active_mailer }}</p>
            </div>
            <a href="{{ route('admin.settings.advance.email-configuration.providers') }}"
                class="text-xs text-indigo-600 hover:underline">Change provider</a>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-center gap-2">
                    <input wire:model="enabled" type="checkbox" id="enabled" class="rounded border-gray-300">
                    <label for="enabled" class="text-sm text-gray-700">Enable Email Sending</label>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="queue_emails" type="checkbox" id="queue_emails" class="rounded border-gray-300">
                    <label for="queue_emails" class="text-sm text-gray-700">Queue Emails</label>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Sender Details</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">From Name</label>
                        <input wire:model="from_name" type="text" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('from_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">From Email</label>
                        <input wire:model="from_email" type="email" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('from_email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Reply-To Email</label>
                        <input wire:model="reply_to_email" type="email" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('reply_to_email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @if ($active_mailer === 'smtp')
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">SMTP Credentials</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Host</label>
                            <input wire:model="host" type="text" placeholder="smtp.example.com" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Port</label>
                            <input wire:model="port" type="text" placeholder="587" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Username</label>
                            <input wire:model="username" type="text" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                            <input wire:model="password" type="password" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Encryption</label>
                            <select wire:model="encryption"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                        </div>
                    </div>
                </div>
            @elseif ($active_mailer === 'custom')
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs text-gray-400">Custom driver credentials are managed by your developer. No fields to configure here unless one has been installed.</p>
                </div>
            @else
                <div class="border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">{{ $mailerLabels[$active_mailer] ?? $active_mailer }} Credentials</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">API Key</label>
                            <input wire:model="api_key" type="password" autocomplete="off"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            @endif

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

</div>
