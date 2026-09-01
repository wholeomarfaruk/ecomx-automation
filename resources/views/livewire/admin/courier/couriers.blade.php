<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($couriers as $courier)
            @php $hasDriver = in_array($courier->driver_key, $installedDrivers); @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $courier->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $courier->description }}</p>
                    </div>
                    <span class="inline-flex text-[10px] font-semibold px-2 py-0.5 rounded-full whitespace-nowrap
                        {{ $courier->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $courier->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="px-5 py-4 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Accounts</span>
                        <span class="font-mono font-medium text-gray-700">{{ $courier->accounts_count }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-400">Shipments</span>
                        <span class="font-mono font-medium text-gray-700">{{ $courier->shipments_count }}</span>
                    </div>

                    @if ($courier->capabilities)
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach ($courier->capabilities as $capability => $enabled)
                                @if ($enabled)
                                    <span class="inline-flex text-[10px] font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                                        {{ \App\Courier\Enums\CourierCapability::tryFrom($capability)?->label() ?? $capability }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if (! $hasDriver)
                        <p class="text-[11px] text-amber-600 bg-amber-50 rounded-lg px-3 py-2">Driver not installed yet — coming soon.</p>
                    @endif
                </div>

                <div class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between gap-2">
                    <a href="{{ route('admin.settings.advance.courier.accounts') }}?courier={{ $courier->id }}"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Manage accounts →</a>

                    @can('courier_configuration.manage')
                        @if ($hasDriver)
                            <button wire:click="toggleActive({{ $courier->id }})" type="button"
                                wire:loading.attr="disabled"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors
                                    {{ $courier->is_active ? 'border-gray-200 text-gray-500 hover:bg-gray-50' : 'border-indigo-200 text-indigo-600 hover:bg-indigo-50' }}">
                                {{ $courier->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

</div>
