@php
    $key = $field['key'];
    $label = $field['label'] ?? $key;
    $items = $this->getFieldValue($key);
    $items = is_array($items) ? $items : [];
    $itemSchema = $field['item_schema'] ?? [];
    $defaultItem = $field['default_item'] ?? [];
@endphp

<div class="sm:col-span-2">
    <div class="flex items-center justify-between mb-2">
        <label class="block text-xs font-medium text-gray-600">{{ $label }}</label>
        <button type="button" wire:click="addRepeaterItem('{{ $key }}', {{ json_encode($defaultItem) }})"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-800 border border-gray-300 rounded-lg px-2.5 py-1">
            + Add {{ $field['item_noun'] ?? 'Item' }}
        </button>
    </div>

    <div class="space-y-3">
        @forelse($items as $index => $item)
            <div wire:key="repeater-{{ $key }}-{{ $index }}" class="rounded-xl border border-gray-200 bg-gray-50/60 p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-500">#{{ $index + 1 }}</span>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="moveRepeaterItem('{{ $key }}', {{ $index }}, -1)"
                            @if($index === 0) disabled @endif
                            class="text-xs text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed px-1.5">↑</button>
                        <button type="button" wire:click="moveRepeaterItem('{{ $key }}', {{ $index }}, 1)"
                            @if($index === count($items) - 1) disabled @endif
                            class="text-xs text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed px-1.5">↓</button>
                        <button type="button" wire:click="removeRepeaterItem('{{ $key }}', {{ $index }})" wire:confirm="Remove this item?"
                            class="text-xs text-red-500 hover:text-red-700 px-1.5">Remove</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($itemSchema as $subField)
                        @php
                            $subKey = $subField['sub_key'];
                            $subType = $subField['type'] ?? 'text';
                            $subLabel = $subField['label'] ?? $subKey;
                            $subValue = $item[$subKey] ?? '';
                        @endphp
                        <div class="{{ in_array($subType, ['textarea', 'html']) ? 'sm:col-span-2' : '' }}">
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ $subLabel }}</label>

                            @if($subType === 'textarea' || $subType === 'html')
                                <textarea
                                    wire:change="updateRepeaterField('{{ $key }}', {{ $index }}, '{{ $subKey }}', $event.target.value)"
                                    rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                >{{ $subValue }}</textarea>
                            @elseif($subType === 'media')
                                <div class="flex items-center gap-2">
                                    @if($subValue)
                                        <img src="{{ $subValue }}" alt="" class="h-10 w-10 rounded-lg object-cover border border-gray-200">
                                    @endif
                                    <button type="button" wire:click="addRepeaterMediaSlot('{{ $key }}', {{ $index }}, '{{ $subKey }}')"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800 border border-gray-300 rounded-lg px-2.5 py-1.5">
                                        {{ $subValue ? 'Change' : 'Select' }} Image
                                    </button>
                                </div>
                            @elseif($subType === 'select')
                                <select
                                    wire:change="updateRepeaterField('{{ $key }}', {{ $index }}, '{{ $subKey }}', $event.target.value)"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                >
                                    @foreach($subField['options'] ?? [] as $optValue => $optLabel)
                                        <option value="{{ $optValue }}" @selected($subValue == $optValue)>{{ $optLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif($subType === 'product')
                                <x-searchable-select
                                    field="content.{{ $key }}.{{ $index }}.{{ $subKey }}"
                                    :value="$subValue"
                                    :options="$this->productOptions"
                                    :images="$this->productImages"
                                    placeholder="— Select a product —"
                                    search-placeholder="Search by product name or code…"
                                />
                            @else
                                <input
                                    type="text"
                                    value="{{ $subValue }}"
                                    wire:change="updateRepeaterField('{{ $key }}', {{ $index }}, '{{ $subKey }}', $event.target.value)"
                                    placeholder="{{ $subField['placeholder'] ?? '' }}"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-xs text-gray-400 italic">No items yet. Click "+ Add {{ $field['item_noun'] ?? 'Item' }}" to add one.</p>
        @endforelse
    </div>
</div>
