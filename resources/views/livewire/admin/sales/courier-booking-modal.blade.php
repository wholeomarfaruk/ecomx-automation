<div>
    {{-- Opens on click (the browser event) — the order's details fill in a moment later. --}}
    <div x-cloak x-data="{ open: @entangle('bookingModal'), loadingId: null }"
        @open-courier-booking.window="open = true; loadingId = $event.detail.orderId"
        x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">
                        Book Courier Shipment @if($bookingOrderId) — Order #{{ $bookingOrderId }} @endif
                    </h2>
                </div>
                <button wire:click="closeBookingModal" @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div x-show="loadingId && Number($wire.bookingOrderId) !== Number(loadingId)" class="px-6 py-10 text-center text-sm text-gray-400">
                <svg class="inline h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Loading order…
            </div>
            <form x-show="!loadingId || Number($wire.bookingOrderId) === Number(loadingId)" wire:submit.prevent="bookShipment" class="px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Courier Account</label>
                    <select wire:model.live="bookingAccountId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select a courier account —</option>
                        @foreach($bookableAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->courier->name }} — {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('bookingAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    @if($bookableAccounts->isEmpty())
                        <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 mt-2">
                            No active courier account can create shipments yet. Add one under
                            <a href="{{ route('admin.settings.advance.courier.accounts') }}" class="underline" wire:navigate>Courier &gt; Accounts</a>.
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Recipient Name</label>
                        <input wire:model="bookingRecipientName" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('bookingRecipientName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Recipient Phone</label>
                        <input wire:model="bookingRecipientPhone" type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('bookingRecipientPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Recipient Address</label>
                    <textarea wire:model="bookingRecipientAddress" rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('bookingRecipientAddress') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">COD Amount</label>
                        <input wire:model="bookingCodAmount" type="number" step="0.01" min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('bookingCodAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Weight (kg)</label>
                        <input wire:model="bookingWeight" type="number" step="0.01" min="0.01"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('bookingWeight') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Quantity</label>
                        <input wire:model="bookingQuantity" type="number" step="1" min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('bookingQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Item Description</label>
                    <textarea wire:model="bookingDescription" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Special Instruction</label>
                    <input wire:model="bookingInstruction" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                @if($this->selectedAccountCourier && ($this->selectedAccountCourier->capabilities['exchange'] ?? false))
                    <div class="rounded-lg border border-gray-200 p-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model.live="bookingIsExchange" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-medium text-gray-700">This is an exchange parcel</span>
                        </label>
                        @if($bookingIsExchange)
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Exchange Item Description</label>
                                <input wire:model="bookingExchangeDescription" type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                @error('bookingExchangeDescription') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button wire:click="closeBookingModal" @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="bookShipment"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="bookShipment">Book Shipment</span>
                        <span wire:loading wire:target="bookShipment">Booking…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
