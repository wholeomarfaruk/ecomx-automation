<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Shipments</h2>
                <p class="text-xs text-gray-400">Every courier booking attempt across all orders</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search order # / tracking..."
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 w-52">
                <select wire:model.live="courierFilter" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Couriers</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                        <th class="px-6 py-3">Order</th>
                        <th class="px-6 py-3">Courier</th>
                        <th class="px-6 py-3">Tracking No.</th>
                        <th class="px-6 py-3">COD</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Created</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($shipments as $shipment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">
                                <a href="{{ route('admin.settings.advance.courier.shipments.show', $shipment) }}" class="hover:text-indigo-600">#{{ $shipment->order_id }}</a>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $shipment->courier->name }}</td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $shipment->tracking_number ?? '—' }}</td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $shipment->cod_amount ? number_format($shipment->cod_amount, 2) : '—' }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $shipment->statusEnum()->badgeClass() }}">
                                    {{ $shipment->statusEnum()->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-xs text-gray-400">{{ $shipment->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @can('courier_configuration.manage')
                                        <button wire:click="syncTracking({{ $shipment->id }})" type="button" wire:loading.attr="disabled"
                                            class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Sync</button>
                                        @if (! in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                                            <button wire:click="cancelShipment({{ $shipment->id }})" type="button" wire:loading.attr="disabled"
                                                wire:confirm="Cancel this shipment?"
                                                class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50">Cancel</button>
                                        @endif
                                    @endcan
                                    <a href="{{ route('admin.settings.advance.courier.shipments.show', $shipment) }}"
                                        class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">View</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-400">No shipments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shipments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $shipments->links() }}</div>
        @endif
    </div>

</div>
