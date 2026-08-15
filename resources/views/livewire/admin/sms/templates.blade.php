<div x-data x-init="$store.pageName = { name: 'SMS Configuration', slug: 'sms-configuration' }" class="space-y-6">

    @include('livewire.admin.sms.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">{{ $editingId ? 'Edit Template' : 'New Template' }}</h2>
            <p class="text-xs text-gray-400">Use {placeholders} in the body — e.g. {code}, {order_id}, {amount}</p>
        </div>
        <form wire:submit.prevent="save" class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Key</label>
                    <input wire:model="key" type="text" placeholder="otp" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('key') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Label</label>
                    <input wire:model="label" type="text" placeholder="OTP Verification" autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('label') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Body</label>
                    <textarea wire:model="body" rows="3" placeholder="Your OTP code is {code}." autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="is_active" type="checkbox" id="is_active" class="rounded border-gray-300">
                    <label for="is_active" class="text-sm text-gray-700">Active</label>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                    Save Template
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="inline-flex items-center px-4 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Templates</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($templates as $template)
                <div class="px-6 py-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $template->label }} <span class="text-xs text-gray-400 font-mono">({{ $template->key }})</span></p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $template->body }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex text-xs font-semibold px-2 py-0.5 rounded-full {{ $template->is_active ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <button wire:click="edit({{ $template->id }})" class="text-xs text-indigo-600 hover:underline">Edit</button>
                        <button wire:click="delete({{ $template->id }})" wire:confirm="Delete this template?" class="text-xs text-red-600 hover:underline">Delete</button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-400">No templates yet.</div>
            @endforelse
        </div>
    </div>

</div>
