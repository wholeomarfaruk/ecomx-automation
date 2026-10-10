<div>
    {{-- Opens on click (the browser event) — the result fills in once the check returns. --}}
    <div x-cloak x-data="{ open: @entangle('modalOpen'), loadingId: null }"
        @open-fraud-check.window="open = true; loadingId = $event.detail.orderId"
        x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false; $wire.close()">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base font-semibold text-gray-900">
                        Fraud Check @if($orderId) — Order #{{ $orderId }} @endif
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $phone ?: '—' }}</p>
                </div>
                <button wire:click="close" @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div x-show="loadingId && Number($wire.orderId) !== Number(loadingId)" class="px-6 py-12 text-center text-sm text-gray-400">
                <svg class="inline h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Checking courier history…
            </div>

            <div x-show="!loadingId || Number($wire.orderId) === Number(loadingId)" class="px-6 py-5 overflow-y-auto relative">
                <div wire:loading.flex wire:target="recheck" class="absolute inset-0 z-10 bg-white/70 items-center justify-center text-sm text-gray-500">
                    <svg class="h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    Re-checking…
                </div>

                @if ($error)
                    <div class="mb-4 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                        {{ $error }}
                        @if (! $ready && $canManage)
                            <a href="{{ route('admin.settings.advance.fraud-checker') }}" class="block mt-1 text-xs font-medium text-red-700 underline">Open Fraud Checker settings →</a>
                        @endif
                        @if ($check)
                            <span class="block mt-1 text-xs text-red-500">Showing the last saved result below.</span>
                        @endif
                    </div>
                @endif

                @if ($check)
                    @include('livewire.admin.sales.partials.fraud-result', ['check' => $check])
                @elseif (! $error)
                    <p class="py-8 text-center text-sm text-gray-400">No result yet.</p>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-3 border-t border-gray-100 bg-gray-50/60">
                <button wire:click="close" @click="open = false" type="button" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">Close</button>
                @if ($ready)
                    <button wire:click="recheck" wire:loading.attr="disabled" wire:target="recheck" type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        Re-check now
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
