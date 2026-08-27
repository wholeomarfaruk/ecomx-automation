<div x-data x-init="$store.pageName = { name: '{{ $editingId ? 'Edit' : 'New' }} Landing Page', slug: 'landing-pages' }">

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <a href="{{ route('admin.landingpages.pages') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">&larr; All Pages</a>
            <h1 class="text-xl font-semibold text-gray-800 mt-1">{{ $editingId ? 'Edit Landing Page' : 'New Landing Page' }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @if($editingId)
                <a href="{{ route('admin.landingpages.pages.preview', $editingId) }}" target="_blank"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition">
                    Preview
                </a>
            @endif
            <button wire:click="save" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Save
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: metadata --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Page Details</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Internal Name</label>
                    <input wire:model.blur="name" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Public Title</label>
                    <input wire:model="title" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Slug</label>
                    <div class="flex items-center gap-1 text-sm text-gray-400">
                        <span>/lp/</span>
                        <input wire:model="slug" type="text"
                            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-gray-900">
                    </div>
                    @error('slug') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Template</label>
                    <select wire:model="templateId" wire:change="selectTemplate($event.target.value)"
                        @if($editingId) disabled @endif
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                    @if($editingId)
                        <p class="text-xs text-gray-400 mt-1">Template can't be changed after creation.</p>
                    @endif
                    @error('templateId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">Header / Footer</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Header</label>
                    <select wire:model="headerMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="global">Global Header</option>
                        <option value="custom">Custom</option>
                        <option value="none">None</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Footer</label>
                    <select wire:model="footerMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="global">Global Footer</option>
                        <option value="custom">Custom</option>
                        <option value="none">None</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700">SEO</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Meta Title</label>
                    <input wire:model="seoMetaTitle" type="text" maxlength="70"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Meta Description</label>
                    <textarea wire:model="seoMetaDescription" rows="3" maxlength="160"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 mb-1">Meta Image URL</label>
                    <input wire:model="seoMetaImage" type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Right: schema-driven content editor --}}
        <div class="lg:col-span-2 space-y-6">
            @forelse($this->schemaSections() as $sectionKey => $section)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4">{{ $section['label'] ?? ucfirst($sectionKey) }}</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @php
                            $fields = $section['fields'] ?? [];
                        @endphp

                        @if(array_is_list($fields))
                            @foreach($fields as $field)
                                @include('livewire.admin.landing-pages.partials.field', ['field' => $field])
                            @endforeach
                        @else
                            @foreach($fields as $fieldName => $field)
                                @if(($field['child'] ?? false) && isset($field['fields']))
                                    <div class="sm:col-span-2">
                                        <p class="text-xs font-medium text-gray-500 mb-2">{{ $field['label'] ?? $fieldName }}</p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-3 border-l-2 border-gray-100">
                                            @foreach($field['fields'] as $childField)
                                                @include('livewire.admin.landing-pages.partials.field', ['field' => $childField])
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    @include('livewire.admin.landing-pages.partials.field', ['field' => $field])
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-400">
                    Select a template to see its editable fields.
                </div>
            @endforelse
        </div>
    </div>
</div>
