@props([
    'field',
    'value'       => null,
    'label'       => '',
    'placeholder' => 'Click to attach files',
    'required'    => false,
])

@php
    $dispatchArgs = json_encode([
        'target'   => $field,
        'multiple' => true,
        'type'     => 'document',
    ]);
    $documents = collect($value ?? [])
        ->map(fn ($id) => \App\Models\File::find($id))
        ->filter();
@endphp

<div class="grid gap-1.5">
    @if($label)
        <label class="block text-sm font-medium text-gray-900">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-0.5">*</span>
            @endif
        </label>
    @endif

    <input wire:model="{{ $field }}" id="{{ $field }}" type="hidden">

    @if($documents->isEmpty())
        <div
            wire:click="$dispatch('openMediaPicker', {{ $dispatchArgs }})"
            class="border-2 border-dashed border-gray-300 rounded-xl cursor-pointer
                   hover:border-indigo-400 hover:bg-indigo-50/50 transition-all
                   flex flex-col items-center justify-center gap-2 text-center p-4"
        >
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">{{ $placeholder }}</p>
                <p class="text-xs text-gray-400 mt-0.5">PDF, Word, Excel, or other documents</p>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
            @foreach($documents as $document)
                <div class="flex items-center gap-3 px-3 py-2.5 bg-white">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-700 truncate" title="{{ $document->name }}">{{ $document->name }}</p>
                    </div>
                    <a href="{{ file_path($document->id) }}" download="{{ $document->name }}" target="_blank"
                        class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 transition shrink-0"
                        title="Download">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </a>
                    <button type="button" wire:click.stop="removeMedia('{{ $field }}', '{{ $document->id }}')"
                        class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition shrink-0"
                        title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button" wire:click="$dispatch('openMediaPicker', {{ $dispatchArgs }})"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 transition mt-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add more documents
        </button>
    @endif

    @error($field)
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
