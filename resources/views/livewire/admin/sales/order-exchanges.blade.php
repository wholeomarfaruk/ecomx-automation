<div>
    @php $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 3), '0'), '.'); @endphp

    @if($exchanges->isNotEmpty() || $canCreate)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Exchanges</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Customer swaps delivered items inside this order — no new order.</p>
                </div>
                @if($canCreate)
                    <button type="button" wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        New exchange
                    </button>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($exchanges as $exchange)
                    @php $balance = $exchange->balance($order); @endphp
                    <div wire:key="exchange-{{ $exchange->id }}" class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Exchange #{{ $exchange->number }}
                                    <span class="text-xs font-normal text-gray-400">· {{ local_time($exchange->created_at)?->format('d M Y') }}</span>
                                </p>
                                @if($exchange->reason)
                                    <p class="text-xs text-gray-500">{{ $exchange->reason }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium {{ $exchange->status->badgeClass() }}">{{ $exchange->status->label() }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-xs">
                            <div class="rounded-lg bg-red-50/50 px-3 py-2">
                                <p class="font-medium text-red-600 mb-1">Coming back</p>
                                @foreach($exchange->returnItems as $line)
                                    <p class="text-gray-700">{{ $qty($line->quantity) }} × {{ $line->orderItem?->product_name }}@if($line->orderItem?->variant_name) <span class="text-gray-400">({{ $line->orderItem->variant_name }})</span>@endif</p>
                                @endforeach
                            </div>
                            <div class="rounded-lg bg-emerald-50/50 px-3 py-2">
                                <p class="font-medium text-emerald-700 mb-1">Going out</p>
                                @foreach($exchange->newItems as $item)
                                    <p class="text-gray-700">{{ $qty($item->quantity) }} × {{ $item->product_name }}@if($item->variant_name) <span class="text-gray-400">({{ $item->variant_name }})</span>@endif · {{ number_format((float) $item->total_amount, 2) }}</p>
                                @endforeach
                            </div>
                        </div>

                        @unless(in_array($exchange->status, [\App\Enums\Sales\ExchangeStatus::CANCELLED, \App\Enums\Sales\ExchangeStatus::FAILED], true))
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-3 text-xs text-gray-500">
                            <span>Credit <span class="text-red-500">−{{ number_format($exchange->creditAmount($order), 2) }}</span></span>
                            <span>New <span class="text-gray-800">{{ number_format($exchange->newAmount(), 2) }}</span></span>
                            @if((float) $exchange->charge_amount > 0)
                                <span>Charge <span class="text-gray-800">+{{ number_format((float) $exchange->charge_amount, 2) }}</span></span>
                            @endif
                            <span class="font-medium {{ $balance >= 0 ? 'text-gray-800' : 'text-amber-700' }}">
                                {{ $balance >= 0 ? 'Customer pays' : 'Refund to customer' }} {{ number_format(abs($balance), 2) }}
                            </span>
                        </div>
                        @endunless

                        @if($exchange->shipment)
                            <div class="flex flex-wrap items-center gap-2 mt-3 rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-xs">
                                <span class="font-medium text-gray-700">{{ $exchange->shipment->courier?->name }}</span>
                                <span class="font-mono text-gray-600">{{ $exchange->shipment->tracking_number ?? '—' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $exchange->shipment->statusEnum()->badgeClass() }}">{{ $exchange->shipment->statusEnum()->label() }}</span>
                                <span class="text-gray-400">COD {{ number_format((float) $exchange->shipment->cod_amount, 2) }}</span>
                                <span class="text-gray-400">· ref {{ $exchange->reference() }}</span>
                            </div>
                        @endif

                        @if($canEdit && $exchange->status->isActive())
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                @if($exchange->status->canShip() && $canCourier)
                                    <button type="button" wire:click="openShip({{ $exchange->id }})"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Send parcel</button>
                                @endif
                                @if($exchange->status->canComplete())
                                    <button type="button" wire:click="complete({{ $exchange->id }})" wire:loading.attr="disabled"
                                        wire:confirm="{{ $exchange->status->canShip() ? 'Swapped in the shop — the customer has the new item and the old one is back? It will be marked returned and restocked.' : 'The old item is back in the shop? It will be marked returned and restocked.' }}"
                                        class="px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">{{ $exchange->status->canShip() ? 'Swapped in shop' : 'Old item received' }}</button>
                                @endif
                                @if($exchange->status->canFail())
                                    <button type="button" wire:click="openFail({{ $exchange->id }})"
                                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Customer refused</button>
                                @endif
                                @if($exchange->status->canDetach())
                                    <button type="button" wire:click="detach({{ $exchange->id }})" wire:loading.attr="disabled"
                                        wire:confirm="Remove this parcel from the exchange? It is NOT cancelled at the courier — cancel it there too if it's still booked."
                                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Remove parcel</button>
                                @endif
                                @if($exchange->status->canShip())
                                    <button type="button" wire:click="cancel({{ $exchange->id }})" wire:loading.attr="disabled"
                                        wire:confirm="Cancel exchange #{{ $exchange->number }}? The new items come off the order and back into stock."
                                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                @endif
                            </div>
                            @if($exchange->status === \App\Enums\Sales\ExchangeStatus::RETURNING)
                                <p class="text-[11px] text-orange-600 mt-2">The courier says the parcel is coming back — once it's in your hands, say which: the old item (exchange done) or the new one (customer refused).</p>
                            @endif
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-gray-400">Customer wants a different size or item? Open an exchange — the difference is collected by the exchange parcel.</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- New exchange --}}
    <div x-cloak x-data="{ open: @entangle('createModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-2xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-visible" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">New Exchange — Order #{{ $order->id }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Old items come back, new items go out, in this same order.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="create" class="overflow-y-auto px-6 py-5 space-y-5">
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">1. What's coming back</p>
                    <div class="space-y-2">
                        @forelse($exchangeable as $row)
                            <div wire:key="exchange-back-{{ $row['item']->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-800">{{ $row['item']->product_name }}@if($row['item']->variant_name) <span class="text-xs text-gray-400">({{ $row['item']->variant_name }})</span>@endif</p>
                                    <p class="text-xs text-gray-400">{{ number_format((float) $row['item']->unit_price, 2) }} each · {{ $qty($row['max']) }} can be exchanged</p>
                                </div>
                                <input wire:model.live.debounce.300ms="back.{{ $row['item']->id }}" type="number" step="1" min="0" max="{{ $row['max'] }}" placeholder="0"
                                    class="w-20 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">Nothing left in this order to exchange.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">2. What goes out instead</p>
                    @if($createModal)
                        <x-searchable-select wire:key="exchange-product-picker-{{ count($newLines) }}"
                            field="productPickerId" :value="$productPickerId" :options="$productOptions"
                            placeholder="— Add a product —" search-placeholder="Search by name or code…" />
                    @endif
                    @error('newLines') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    <div class="space-y-2 mt-2">
                        @foreach($newLines as $i => $line)
                            @php $variants = $variantOptions->get((int) $line['product_id']); @endphp
                            <div wire:key="exchange-new-{{ $i }}-{{ $line['product_id'] }}" class="rounded-lg border border-gray-200 px-3 py-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="flex-1 min-w-[140px] text-sm text-gray-800">{{ $productOptions[$line['product_id']] ?? 'Product' }}</span>
                                    @if($variants && $variants->isNotEmpty())
                                        <select wire:model.live="newLines.{{ $i }}.variant_id"
                                            class="w-40 rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            <option value="">— Variant —</option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}">{{ $variant->sku }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <input wire:model.live.debounce.300ms="newLines.{{ $i }}.quantity" type="number" step="1" min="1" title="Quantity"
                                        class="w-16 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <span class="text-xs text-gray-400">×</span>
                                    <input wire:model.live.debounce.300ms="newLines.{{ $i }}.unit_price" type="number" step="0.01" min="0" title="Price"
                                        class="w-24 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-right focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <button type="button" wire:click="removeNewLine({{ $i }})" title="Remove"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @error("newLines.{$i}.variant_id") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                @error("newLines.{$i}.quantity") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                @error("newLines.{$i}.unit_price") <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">3. Exchange delivery charge (customer pays)</label>
                        <input wire:model.live.debounce.300ms="charge" type="number" step="0.01" min="0" placeholder="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <p class="text-[11px] text-gray-400 mt-1">0 if the shop bears it (e.g. wrong size sent).</p>
                        @error('charge') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reason</label>
                        <input wire:model="reason" type="text" placeholder="e.g. Size too small, wants 1950 dress instead"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                @if($preview)
                    <div class="rounded-xl border px-4 py-3 text-sm {{ $preview['balance'] >= 0 ? 'border-indigo-100 bg-indigo-50/50' : 'border-amber-200 bg-amber-50' }}">
                        <div class="flex flex-wrap gap-x-5 gap-y-1 text-gray-600">
                            <span>Credit <span class="text-red-500">−{{ number_format($preview['credit'], 2) }}</span></span>
                            <span>New <span class="text-gray-800">{{ number_format($preview['new'], 2) }}</span></span>
                            <span>Charge <span class="text-gray-800">+{{ number_format($preview['charge'], 2) }}</span></span>
                        </div>
                        <p class="mt-1 font-semibold {{ $preview['balance'] >= 0 ? 'text-indigo-700' : 'text-amber-800' }}">
                            @if($preview['balance'] >= 0)
                                Customer pays {{ number_format($preview['balance'], 2) }} — collected by the exchange parcel
                            @else
                                Refund to customer {{ number_format(abs($preview['balance']), 2) }} — use Refund on the order (parcel COD 0)
                            @endif
                        </p>
                        <p class="text-[11px] text-gray-400 mt-1">Credit is the price paid for the old items, after their share of the order discount. The new items leave stock now; the old ones are restocked when you mark them received.</p>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Open exchange</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Exchange parcel --}}
    <div x-cloak x-data="{ open: @entangle('shipModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-visible" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Exchange Parcel</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Its own parcel — the order's first delivery isn't touched.</p>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="ship" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5 text-xs font-medium">
                    <button type="button" wire:click="$set('shipMode', 'book')"
                        class="px-3 py-1.5 rounded-md transition {{ $shipMode === 'book' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">Book via API</button>
                    <button type="button" wire:click="$set('shipMode', 'attach')"
                        class="px-3 py-1.5 rounded-md transition {{ $shipMode === 'attach' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">Booked on courier panel</button>
                </div>

                @if($shipMode === 'book')
                    <p class="text-[11px] text-gray-500 bg-amber-50 rounded-lg px-3 py-2">Booked as a normal delivery with the instruction below telling the rider to collect the old item — no courier here books a dedicated exchange parcel through its API yet. To use the courier's own exchange option, book it on their panel and use "Booked on courier panel".</p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Courier <span class="text-red-500">*</span></label>
                        <select wire:model="shipAccountId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            @foreach($bookableAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->courier->name }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('shipAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">COD</label>
                            <input wire:model="shipCod" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('shipCod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Weight (kg)</label>
                            <input wire:model="shipWeight" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Quantity</label>
                            <input wire:model="shipQuantity" type="number" step="1" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Item description</label>
                        <textarea wire:model="shipDescription" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Instruction for the rider</label>
                        <textarea wire:model="shipInstruction" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Courier <span class="text-red-500">*</span></label>
                        <select wire:model="shipCourierId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                            @endforeach
                        </select>
                        @error('shipCourierId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Consignment / tracking ID <span class="text-red-500">*</span></label>
                            <input wire:model="shipTracking" type="text" placeholder="e.g. DS301026ABCD"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('shipTracking') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">COD</label>
                            <input wire:model="shipCod" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('shipCod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400">The courier's webhooks for this ID will move this exchange (never the order's own delivery). Use the exact ID the courier shows — for Pathao, the consignment ID.</p>
                @endif

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">{{ $shipMode === 'book' ? 'Book parcel' : 'Attach parcel' }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Customer refused --}}
    <div x-cloak x-data="{ open: @entangle('failModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Customer refused the exchange</h2>
                <p class="text-xs text-gray-400 mt-0.5">The new item came back — it's restocked; the customer keeps the old one.</p>
            </div>
            <form wire:submit.prevent="markFailed" class="px-6 py-5 space-y-4">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input wire:model="failKeepCharge" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Customer still pays the exchange delivery charge</span>
                </label>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</button>
                    <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Mark failed</button>
                </div>
            </form>
        </div>
    </div>
</div>
