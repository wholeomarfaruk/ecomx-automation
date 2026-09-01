<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.settings.advance.courier.shipments') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 inline-flex items-center gap-1.5">
            ← Back to Shipments
        </a>
        <div class="flex items-center gap-2">
            @can('courier_configuration.manage')
                <button wire:click="syncTracking" type="button" wire:loading.attr="disabled"
                    class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Sync Tracking</button>
                @if (! in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                    <button wire:click="cancelShipment" type="button" wire:loading.attr="disabled" wire:confirm="Cancel this shipment?"
                        class="text-sm font-medium px-4 py-2 rounded-lg border border-red-200 text-red-500 hover:bg-red-50">Cancel Shipment</button>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            {{-- ── Header Card ── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Order</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $shipment->order_id }}</p>
                    </div>
                    <span class="inline-flex text-xs font-semibold px-3 py-1.5 rounded-full {{ $shipment->statusEnum()->badgeClass() }}">
                        {{ $shipment->statusEnum()->label() }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Courier</p>
                        <p class="text-sm font-medium text-gray-900">{{ $shipment->courier->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Account</p>
                        <p class="text-sm font-medium text-gray-900">{{ $shipment->courierAccount->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tracking No.</p>
                        <p class="text-sm font-mono font-medium text-gray-900">{{ $shipment->tracking_number ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Consignment ID</p>
                        <p class="text-sm font-mono font-medium text-gray-900">{{ $shipment->consignment_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">COD Amount</p>
                        <p class="text-sm font-medium text-gray-900">{{ $shipment->cod_amount ? number_format($shipment->cod_amount, 2) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Delivery Charge</p>
                        <p class="text-sm font-medium text-gray-900">{{ $shipment->delivery_charge ? number_format($shipment->delivery_charge, 2) : '—' }}</p>
                    </div>
                </div>

                @if ($shipment->error_message)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-red-500 uppercase tracking-wide mb-1">Error</p>
                        <p class="text-sm text-red-600">{{ $shipment->error_message }}</p>
                    </div>
                @endif
            </div>

            {{-- ── Tracking Timeline ── --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Tracking Timeline</h2>
                </div>
                <div class="px-6 py-5">
                    @forelse ($shipment->trackingEvents as $event)
                        <div class="flex gap-4 {{ ! $loop->last ? 'pb-6' : '' }}">
                            <div class="flex flex-col items-center">
                                <span class="w-2.5 h-2.5 rounded-full {{ $loop->last ? 'bg-indigo-600' : 'bg-gray-300' }} mt-1"></span>
                                @if (! $loop->last)
                                    <span class="w-px flex-1 bg-gray-200 mt-1"></span>
                                @endif
                            </div>
                            <div class="flex-1 -mt-0.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-900">{{ \App\Enums\Sales\CourierStatus::tryFrom($event->status)?->label() ?? $event->status }}</p>
                                    @if ($event->raw_status && $event->raw_status !== $event->status)
                                        <span class="text-[10px] font-mono text-gray-400">({{ $event->raw_status }})</span>
                                    @endif
                                </div>
                                @if ($event->message)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $event->message }}</p>
                                @endif
                                @if ($event->location)
                                    <p class="text-xs text-gray-400 mt-0.5">📍 {{ $event->location }}</p>
                                @endif
                                <p class="text-[11px] text-gray-400 mt-1">{{ $event->event_at?->format('d M Y, h:i A') ?? $event->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No tracking events yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Raw Payloads ── --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Request Payload</h2>
                </div>
                <div class="px-6 py-5">
                    <pre class="text-[11px] text-gray-600 bg-gray-50 rounded-lg p-4 overflow-x-auto max-h-64">{{ json_encode(json_decode($shipment->request_payload ?? '{}'), JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Response Payload</h2>
                </div>
                <div class="px-6 py-5">
                    <pre class="text-[11px] text-gray-600 bg-gray-50 rounded-lg p-4 overflow-x-auto max-h-64">{{ json_encode(json_decode($shipment->response_payload ?? '{}'), JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>

    </div>

</div>
