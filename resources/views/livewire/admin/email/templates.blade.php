<div x-data x-init="$store.pageName = { name: 'Email Configuration', slug: 'email-configuration' }" class="space-y-6">

    @include('livewire.admin.email.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Email Templates</h2>
            <p class="text-xs text-gray-400">Choose a ready-made design for each email type. Preview before activating — designs are managed by your developer.</p>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse ($prebuiltTemplates as $prebuilt)
                @php $isActive = in_array($prebuilt['template_key'], $activeTemplateKeys); @endphp
                <div class="px-6 py-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800">{{ $prebuilt['label'] }}</p>
                            @if ($isActive)
                                <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Active</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $prebuilt['description'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Used for: <span class="font-mono">{{ $prebuilt['template_key'] }}</span></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button wire:click="preview('{{ $prebuilt['key'] }}')" type="button"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-600 hover:bg-gray-50">
                            Preview
                        </button>
                        <button wire:click="activate('{{ $prebuilt['key'] }}')" wire:confirm="Use this template for '{{ $prebuilt['template_key'] }}' emails?" type="button"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-white
                                {{ $isActive ? 'bg-gray-300 cursor-default' : 'bg-indigo-600 hover:bg-indigo-700' }}"
                            @if ($isActive) disabled @endif>
                            {{ $isActive ? 'Activated' : 'Use This Template' }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-400">No templates available yet.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Read-only Preview ── --}}
    @if ($previewKey)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" wire:click.self="closePreview">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3 shrink-0">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Preview</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span class="font-semibold text-gray-700">Subject:</span> {{ $previewSubject ?: '(empty)' }}
                        </p>
                    </div>
                    <button wire:click="closePreview" type="button" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <div class="flex-1 overflow-hidden">
                    <iframe srcdoc="{{ $previewHtml }}" class="w-full h-full" style="min-height: 480px;" sandbox=""></iframe>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button wire:click="closePreview" type="button"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Close
                    </button>
                    @php $previewedPrebuilt = collect($prebuiltTemplates)->firstWhere('key', $previewKey); @endphp
                    @if ($previewedPrebuilt && ! in_array($previewedPrebuilt['template_key'], $activeTemplateKeys))
                        <button wire:click="activate('{{ $previewKey }}')" wire:confirm="Use this template for '{{ $previewedPrebuilt['template_key'] }}' emails?" type="button"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                            Use This Template
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>
