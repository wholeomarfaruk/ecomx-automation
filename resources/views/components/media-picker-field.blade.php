@props([
    'field',
    'value'       => null,
    'label'       => '',
    'placeholder' => 'Click to select a file',
    'type'        => null,
    'multiple'    => false,
    'required'    => false,
])

@php
    $dispatchArgs = json_encode([
        'target'   => $field,
        'multiple' => (bool) $multiple,
        'type'     => $type,
    ]);
@endphp

<div class="grid gap-1.5">
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-900">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{-- Hidden wire:model input --}}
    <input wire:model="{{ $field }}" id="{{ $field }}" type="hidden">

    {{-- ── Empty state ── --}}
    @if(!$value || ($multiple && is_array($value) && empty($value)))
        <div
            wire:click="$dispatch('openMediaPicker', {{ $dispatchArgs }})"
            class="min-h-32 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer
                   hover:border-indigo-400 hover:bg-indigo-50/50 transition-all
                   flex flex-col items-center justify-center gap-2 text-center p-4"
        >
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                @if($type === 'video')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                @endif
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">{{ $placeholder }}</p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $multiple ? 'Select one or more files' : 'Select a file' }}
                </p>
            </div>
        </div>

    {{-- ── Filled: multiple ── --}}
    @elseif($multiple && is_array($value) && !empty($value))
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <div class="flex flex-wrap gap-3">
                @foreach($value as $itemId)
                    @php $itemId = is_array($itemId) ? ($itemId['id'] ?? '') : $itemId; @endphp
                    <div class="relative group">
                        <a data-fancybox="{{ $field }}"
                           href="{{ file_path($itemId) }}"
                           @if($type === 'video') data-type="video" @endif
                           class="block h-20 w-20 rounded-lg border border-gray-200 overflow-hidden bg-gray-200">
                            @if($type === 'video')
                                <video class="h-full w-full object-cover" preload="metadata">
                                    <source src="{{ file_path($itemId) }}">
                                </video>
                            @else
                                <img src="{{ file_path($itemId) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </a>
                        <button
                            type="button"
                            wire:click.stop="removeMedia('{{ $field }}', '{{ $itemId }}')"
                            class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-red-500 text-white
                                   flex items-center justify-center opacity-0 group-hover:opacity-100
                                   transition hover:bg-red-600 shadow"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach

                {{-- Add more button --}}
                <button
                    type="button"
                    wire:click="$dispatch('openMediaPicker', {{ $dispatchArgs }})"
                    class="h-20 w-20 rounded-lg border-2 border-dashed border-gray-300 flex flex-col
                           items-center justify-center text-gray-400 hover:border-indigo-400
                           hover:text-indigo-500 transition cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-xs mt-1">Add</span>
                </button>
            </div>

            <p class="text-xs text-gray-400 mt-2">{{ count($value) }} file(s) selected</p>
        </div>

    {{-- ── Filled: single ── --}}
    @else
        <div class="relative group rounded-xl border border-gray-200 overflow-hidden bg-gray-50">
            @if($type === 'video')
                <video class="w-full max-h-40 object-cover" controls preload="metadata">
                    <source src="{{ file_path($value) }}">
                </video>
            @else
                <img src="{{ file_path($value) }}" alt="" class="w-full max-h-40 object-contain">
            @endif

            {{-- Overlay on hover --}}
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                <a
                    data-fancybox
                    href="{{ file_path($value) }}"
                    @if($type === 'video') data-type="video" @endif
                    class="px-3 py-1.5 bg-white/90 text-gray-900 rounded-lg text-xs font-medium hover:bg-white transition"
                >
                    View
                </a>
                <button
                    type="button"
                    wire:click="$dispatch('openMediaPicker', {{ $dispatchArgs }})"
                    class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition"
                >
                    Change
                </button>
                <button
                    type="button"
                    wire:click.stop="removeMedia('{{ $field }}')"
                    class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs font-medium hover:bg-red-600 transition"
                >
                    Remove
                </button>
            </div>
        </div>
    @endif

    {{-- Validation error --}}
    @error($field)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
