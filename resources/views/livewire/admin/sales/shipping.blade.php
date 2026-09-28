<div x-data x-init="$store.pageName = { name: 'Shipping', slug: 'sales-shipping' }">

    @php
        $input = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
        $label = 'block text-xs font-medium text-gray-600 mb-1.5';
        $error = 'text-xs text-red-500 mt-1';
        $selectedType = \App\Enums\Sales\ShippingRateType::tryFrom($method['rate_type'] ?? '') ?? \App\Enums\Sales\ShippingRateType::FLAT;
    @endphp

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <p class="text-sm text-gray-500 max-w-2xl">
            Zones are the delivery areas customers pick at checkout. Each zone needs at least one active method —
            when a zone has more than one, the customer chooses between them.
        </p>
        <button wire:click="createZone" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Zone
        </button>
    </div>

    {{-- Zones --}}
    <div class="space-y-5">
        @forelse ($zones as $z)
            <div wire:key="zone-{{ $z->id }}" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden {{ $z->is_active ? '' : 'opacity-70' }}">
                <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-semibold text-gray-800">{{ $z->name }}</h2>
                            <span class="px-2 py-0.5 text-[11px] font-mono text-gray-500 bg-gray-100 rounded">{{ $z->code }}</span>
                            @unless ($z->is_active)
                                <span class="px-2 py-0.5 text-[11px] font-medium text-amber-700 bg-amber-50 rounded">Hidden at checkout</span>
                            @endunless
                        </div>
                    </div>
                    <button wire:click="createMethod({{ $z->id }})" type="button"
                        class="px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">+ Method</button>
                    <button wire:click="toggleZone({{ $z->id }})" type="button"
                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">{{ $z->is_active ? 'Disable' : 'Enable' }}</button>
                    <button wire:click="editZone({{ $z->id }})" type="button"
                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Edit</button>
                    <button wire:click="deleteZone({{ $z->id }})" wire:confirm="Delete the zone &quot;{{ $z->name }}&quot; and all its methods? Past orders keep their delivery details." type="button"
                        class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">Delete</button>
                </div>

                @if ($z->methods->isEmpty())
                    <p class="px-5 py-6 text-sm text-gray-400">No methods yet — this zone won't show at checkout until it has an active method.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 bg-gray-50">
                                    <th class="px-5 py-2.5 font-medium">Method</th>
                                    <th class="px-5 py-2.5 font-medium">Rate</th>
                                    <th class="px-5 py-2.5 font-medium">Free over</th>
                                    <th class="px-5 py-2.5 font-medium">Status</th>
                                    <th class="px-5 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($z->methods as $m)
                                    <tr wire:key="method-{{ $m->id }}">
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-gray-800">{{ $m->name }}</div>
                                            @if ($m->delivery_time)
                                                <div class="text-xs text-gray-400">{{ $m->delivery_time }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-gray-700">
                                            <div>{{ $m->rateSummary() }}</div>
                                            <div class="text-xs text-gray-400">{{ $m->rate_type->label() }}</div>
                                        </td>
                                        <td class="px-5 py-3 text-gray-700">{{ $m->free_over !== null ? '৳' . number_format($m->free_over) : '—' }}</td>
                                        <td class="px-5 py-3">
                                            <button wire:click="toggleMethod({{ $m->id }})" type="button"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full {{ $m->is_active ? 'text-emerald-700 bg-emerald-50' : 'text-gray-500 bg-gray-100' }}">
                                                {{ $m->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <button wire:click="editMethod({{ $m->id }})" type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                                            <button wire:click="deleteMethod({{ $m->id }})" wire:confirm="Delete the method &quot;{{ $m->name }}&quot;?" type="button" class="ml-3 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 px-5 py-12 text-center">
                <p class="text-sm text-gray-500 mb-4">No delivery zones yet. Checkout can't take orders until one zone has an active method.</p>
                <button wire:click="createZone" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Add Zone</button>
            </div>
        @endforelse
    </div>

    {{-- Zone modal --}}
    <div x-cloak x-data="{ open: @entangle('zoneModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <form wire:submit="saveZone" @click.outside="open = false"
            class="w-full max-w-md max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-sm font-semibold text-gray-800">{{ $editingZoneId ? 'Edit Zone' : 'Add Zone' }}</h3>
            </div>
            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                    <input wire:model="zone.name" type="text" placeholder="e.g. Dhaka Suburbs" class="{{ $input }}">
                    @error('zone.name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Code</label>
                    <input wire:model="zone.code" type="text" placeholder="Auto from name, e.g. dhaka-suburbs" class="{{ $input }} font-mono">
                    <p class="text-xs text-gray-400 mt-1">Offers with a "Shipping Method" condition match this code.</p>
                    @error('zone.code') <p class="{{ $error }}">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="{{ $label }}">Order</label>
                        <input wire:model="zone.sort_order" type="number" min="0" class="{{ $input }}">
                        @error('zone.sort_order') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                        <input wire:model="zone.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        Show at checkout
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 shrink-0">
                <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="saveZone">Save</button>
            </div>
        </form>
    </div>

    {{-- Method modal --}}
    <div x-cloak x-data="{ open: @entangle('methodModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <form wire:submit="saveMethod" @click.outside="open = false"
            class="w-full max-w-lg max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 class="text-sm font-semibold text-gray-800">{{ $editingMethodId ? 'Edit Method' : 'Add Method' }}</h3>
            </div>
            <div class="px-6 py-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Name <span class="text-red-500">*</span></label>
                        <input wire:model="method.name" type="text" placeholder="Standard Delivery" class="{{ $input }}">
                        @error('method.name') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Delivery time</label>
                        <input wire:model="method.delivery_time" type="text" placeholder="e.g. 1–2 days" class="{{ $input }}">
                        @error('method.delivery_time') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Rate type</label>
                    <select wire:model.live="method.rate_type" class="{{ $input }} bg-white">
                        @foreach ($rateTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">{{ $selectedType->hint() }}</p>
                </div>

                {{-- Rate fields for the chosen type --}}
                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 space-y-4">
                    @switch ($selectedType)
                        @case (\App\Enums\Sales\ShippingRateType::FLAT)
                            <div>
                                <label class="{{ $label }}">Charge (৳)</label>
                                <input wire:model="method.amount" type="number" step="0.01" min="0" class="{{ $input }}">
                                @error('method.amount') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                            @break

                        @case (\App\Enums\Sales\ShippingRateType::WEIGHT)
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="{{ $label }}">Base weight (kg)</label>
                                    <input wire:model="method.base_weight" type="number" step="0.001" min="0" class="{{ $input }}">
                                    @error('method.base_weight') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Base charge (৳)</label>
                                    <input wire:model="method.base_charge" type="number" step="0.01" min="0" class="{{ $input }}">
                                    @error('method.base_charge') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Per extra kg (৳)</label>
                                    <input wire:model="method.per_kg" type="number" step="0.01" min="0" class="{{ $input }}">
                                    @error('method.per_kg') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">Uses each product's weight from its Shipping tab. Extra weight is rounded up to the next kg.</p>
                            @break

                        @case (\App\Enums\Sales\ShippingRateType::QUANTITY)
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="{{ $label }}">First item (৳)</label>
                                    <input wire:model="method.first_charge" type="number" step="0.01" min="0" class="{{ $input }}">
                                    @error('method.first_charge') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Each extra item (৳)</label>
                                    <input wire:model="method.additional_charge" type="number" step="0.01" min="0" class="{{ $input }}">
                                    @error('method.additional_charge') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            @break

                        @case (\App\Enums\Sales\ShippingRateType::PERCENTAGE)
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="{{ $label }}">Percent (%)</label>
                                    <input wire:model="method.percent" type="number" step="0.01" min="0" max="100" class="{{ $input }}">
                                    @error('method.percent') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Minimum (৳)</label>
                                    <input wire:model="method.min" type="number" step="0.01" min="0" class="{{ $input }}">
                                    @error('method.min') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Maximum (৳)</label>
                                    <input wire:model="method.max" type="number" step="0.01" min="0" placeholder="No limit" class="{{ $input }}">
                                    @error('method.max') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            @break

                        @case (\App\Enums\Sales\ShippingRateType::FREE)
                            <p class="text-sm text-gray-500">No charge for this method.</p>
                            @break
                    @endswitch

                    @if ($selectedType->usesBands())
                        @php $unit = $selectedType === \App\Enums\Sales\ShippingRateType::WEIGHT_BANDS ? 'kg' : '৳'; @endphp
                        <div>
                            <div class="grid grid-cols-[1fr_1fr_auto] gap-2 mb-1.5">
                                <span class="{{ $label }} mb-0">{{ $selectedType === \App\Enums\Sales\ShippingRateType::WEIGHT_BANDS ? 'Weight up to (kg)' : 'Cart amount up to (৳)' }}</span>
                                <span class="{{ $label }} mb-0">Charge (৳)</span>
                                <span class="w-7"></span>
                            </div>
                            <div class="space-y-2">
                                @foreach ($method['bands'] as $i => $band)
                                    <div wire:key="band-{{ $i }}" class="grid grid-cols-[1fr_1fr_auto] gap-2 items-start">
                                        <div>
                                            <input wire:model="method.bands.{{ $i }}.up_to" type="number" step="0.01" min="0" placeholder="and above" class="{{ $input }}">
                                            @error("method.bands.$i.up_to") <p class="{{ $error }}">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <input wire:model="method.bands.{{ $i }}.charge" type="number" step="0.01" min="0" class="{{ $input }}">
                                            @error("method.bands.$i.charge") <p class="{{ $error }}">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="removeBand({{ $i }})" type="button" class="w-7 h-9 text-gray-400 hover:text-red-500" aria-label="Remove band">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            @error('method.bands') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            <button wire:click="addBand" type="button" class="mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Add band</button>
                            <p class="text-xs text-gray-400 mt-2">Leave one "up to" empty for an open-ended top band ({{ $unit === 'kg' ? 'heavier' : 'bigger' }} than every other band).</p>
                        </div>

                        @if ($selectedType === \App\Enums\Sales\ShippingRateType::WEIGHT_BANDS)
                            <div>
                                <label class="{{ $label }}">Past the last band, per extra kg (৳)</label>
                                <input wire:model="method.extra_per_kg" type="number" step="0.01" min="0" placeholder="0" class="{{ $input }}">
                                @error('method.extra_per_kg') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @endif

                    @if ($selectedType->usesWeight())
                        <div class="grid grid-cols-2 gap-3 items-end">
                            <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                                <input wire:model.live="method.volumetric" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                                Use volumetric weight
                            </label>
                            @if ($method['volumetric'])
                                <div>
                                    <label class="{{ $label }}">Divisor (L×W×H cm ÷)</label>
                                    <input wire:model="method.divisor" type="number" step="1" min="1" class="{{ $input }}">
                                    @error('method.divisor') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                        @if ($method['volumetric'])
                            <p class="text-xs text-gray-400">Bulky items count at whichever is larger: actual weight or L×W×H ÷ divisor.</p>
                        @endif
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="{{ $label }}">Free over (৳)</label>
                        <input wire:model="method.free_over" type="number" step="0.01" min="0" placeholder="Never" class="{{ $input }}">
                        @error('method.free_over') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Order</label>
                        <input wire:model="method.sort_order" type="number" min="0" class="{{ $input }}">
                        @error('method.sort_order') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex items-center gap-2 pb-2 text-sm text-gray-700">
                        <input wire:model="method.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600">
                        Active
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 shrink-0">
                <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700" wire:loading.attr="disabled" wire:target="saveMethod">Save</button>
            </div>
        </form>
    </div>
</div>
