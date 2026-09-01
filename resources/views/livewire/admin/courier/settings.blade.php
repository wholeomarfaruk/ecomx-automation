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

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden" x-data="{ copied: null }">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Webhook URLs</h2>
            <p class="text-xs text-gray-400">
                Register these in each courier's merchant panel to enable instant status updates. None of these
                couriers sign their webhook calls, so this app generates its own secret and appends it as
                <code class="bg-gray-100 px-1 py-0.5 rounded text-[11px]">?secret=…</code> — anyone who registers
                the URL without generating a secret first is accepting unauthenticated status updates.
            </p>
        </div>
        <div class="px-6 py-5 space-y-3">
            @foreach ($couriers as $courier)
                <div class="p-3 rounded-lg bg-gray-50 space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-gray-900">{{ $courier->name }}</p>
                        @if ($courier->webhook_secret)
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                </svg>
                                Secured
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                                No secret set
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <input readonly value="{{ $courier->webhookUrl() }}"
                            x-ref="url-{{ $courier->id }}"
                            class="flex-1 text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                        <button type="button"
                            @click="navigator.clipboard.writeText($refs['url-{{ $courier->id }}'].value); copied = {{ $courier->id }}; setTimeout(() => copied = null, 1500)"
                            class="shrink-0 px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <span x-show="copied !== {{ $courier->id }}">Copy</span>
                            <span x-show="copied === {{ $courier->id }}" class="text-emerald-600">Copied!</span>
                        </button>

                        @php
                            $confirmMessage = $courier->webhook_secret
                                ? "Regenerating invalidates the old secret for {$courier->name} — update the callback URL in their merchant panel afterward, or their webhooks will start failing. Continue?"
                                : "Generate a webhook secret for {$courier->name}?";
                        @endphp
                        <button wire:click="generateSecret({{ $courier->id }})" type="button"
                            wire:confirm="{{ $confirmMessage }}"
                            class="shrink-0 px-3 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            {{ $courier->webhook_secret ? 'Regenerate' : 'Generate Secret' }}
                        </button>
                    </div>

                    @if ($courier->driver_key === 'pathao' && $courier->webhook_secret)
                        <div class="flex items-center gap-2">
                            <input readonly value="{{ $courier->webhook_secret }}"
                                x-ref="secret-{{ $courier->id }}"
                                class="flex-1 text-xs font-mono text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2 focus:outline-none">
                            <button type="button"
                                @click="navigator.clipboard.writeText($refs['secret-{{ $courier->id }}'].value); copied = 'secret-{{ $courier->id }}'; setTimeout(() => copied = null, 1500)"
                                class="shrink-0 px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                <span x-show="copied !== 'secret-{{ $courier->id }}'">Copy secret</span>
                                <span x-show="copied === 'secret-{{ $courier->id }}'" class="text-emerald-600">Copied!</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400">
                            Paste the URL above as-is (no ?secret=) into Pathao's webhook URL field, and paste this
                            secret into Pathao's "Webhook Secret" field on their dashboard — Pathao echoes it back
                            in the <code class="bg-gray-100 px-1 rounded">X-PATHAO-Signature</code> header on every call
                            instead of accepting it back as a query string.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

</div>
