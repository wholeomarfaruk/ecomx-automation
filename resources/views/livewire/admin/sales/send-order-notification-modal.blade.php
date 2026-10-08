<div>
    {{-- Opens on click (the browser event) — the order's details fill in a moment later. --}}
    <div x-cloak x-data="{ open: @entangle('modalOpen'), loadingId: null }"
        @open-order-notification.window="open = true; loadingId = $event.detail.orderId"
        x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">
                        Send Notification @if($orderId) — Order #{{ $orderId }} @endif
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">SMS to the customer</p>
                </div>
                <button wire:click="close" @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div x-show="loadingId && Number($wire.orderId) !== Number(loadingId)" class="px-6 py-10 text-center text-sm text-gray-400">
                <svg class="inline h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Loading order…
            </div>

            <form x-show="!loadingId || Number($wire.orderId) === Number(loadingId)" wire:submit.prevent="send" class="px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Message type</label>
                    <select wire:model.live="templateKey" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach($templates as $template)
                            <option value="{{ $template->key }}">{{ $template->label }} ({{ $template->key }})</option>
                        @endforeach
                        <option value="{{ \App\Livewire\Admin\Sales\SendOrderNotificationModal::CUSTOM }}">Custom message</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Templates are managed under SMS &gt; Templates.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Phone</label>
                    <input wire:model="phone" type="text" inputmode="tel"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div x-data="{
                        text: $wire.entangle('message'),
                        get len() { return (this.text || '').length },
                        get unicode() { return /[^\u0000-\u007F]/.test(this.text || '') },
                        get parts() { const per = this.unicode ? 70 : 160, multi = this.unicode ? 67 : 153; return this.len <= per ? (this.len ? 1 : 0) : Math.ceil(this.len / multi) }
                    }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-medium text-gray-600">Message</label>
                        <span class="text-[11px] text-gray-400" x-text="len + ' chars · ' + parts + ' SMS' + (unicode ? ' (Unicode)' : '')"></span>
                    </div>
                    <textarea x-model="text" rows="5" placeholder="Type your message…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button wire:click="close" @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="send"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60 transition">
                        <span wire:loading.remove wire:target="send">Send SMS</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
