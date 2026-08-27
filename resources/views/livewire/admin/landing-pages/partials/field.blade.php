@php
    $key = $field['key'] ?? null;
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? $key;
@endphp

@if($key && $type === 'repeater')
    @include('livewire.admin.landing-pages.partials.repeater', ['field' => $field])
@elseif($key)
    <div class="{{ in_array($type, ['textarea', 'html']) ? 'sm:col-span-2' : '' }}">
        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>

        @if($type === 'textarea' || $type === 'html')
            <textarea
                wire:key="field-{{ $key }}"
                value="{{ $this->getFieldValue($key) }}"
                wire:change="updateField('{{ $key }}', $event.target.value)"
                rows="3"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            >{{ $this->getFieldValue($key) }}</textarea>
        @elseif($type === 'media')
            <div class="flex items-center gap-2">
                @if($this->getFieldValue($key))
                    <img src="{{ $this->getFieldValue($key) }}" alt="" class="h-12 w-12 rounded-lg object-cover border border-gray-200">
                @endif
                <button type="button" wire:click="addMediaSlot('{{ $key }}')"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 border border-gray-300 rounded-lg px-3 py-2">
                    {{ $this->getFieldValue($key) ? 'Change Image' : 'Select Image' }}
                </button>
            </div>
        @elseif($type === 'color')
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    value="{{ $this->getFieldValue($key) ?: '#000000' }}"
                    wire:change="updateField('{{ $key }}', $event.target.value)"
                    class="h-9 w-12 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                >
                <input
                    type="text"
                    value="{{ $this->getFieldValue($key) }}"
                    wire:change="updateField('{{ $key }}', $event.target.value)"
                    placeholder="#000000"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
            </div>
        @elseif($type === 'select')
            <select
                wire:key="field-{{ $key }}"
                wire:change="updateField('{{ $key }}', $event.target.value)"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            >
                @foreach($field['options'] ?? [] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected($this->getFieldValue($key) == $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
        @else
            <input
                wire:key="field-{{ $key }}"
                type="text"
                value="{{ $this->getFieldValue($key) }}"
                wire:change="updateField('{{ $key }}', $event.target.value)"
                placeholder="{{ $field['placeholder'] ?? '' }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            >
        @endif
    </div>
@endif
