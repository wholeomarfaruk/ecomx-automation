<div x-data x-init="$store.pageName = { name: 'Landing Page Settings', slug: 'landing-page-settings' }">

    <h1 class="text-xl font-semibold text-gray-800 mb-6">Landing Page Settings</h1>

    <div class="max-w-xl bg-white rounded-2xl shadow-sm border border-gray-200 p-5 space-y-5">

        <div>
            <label class="block text-sm font-medium text-gray-900 mb-1">Default Template</label>
            <select wire:model="defaultTemplateId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">None</option>
                @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-900 mb-1">Default SEO Title</label>
            <input wire:model="defaultSeoTitle" type="text" maxlength="70"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-900 mb-1">Default SEO Description</label>
            <textarea wire:model="defaultSeoDescription" rows="3" maxlength="160"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Default Header</label>
                <select wire:model="defaultHeaderMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="global">Global Header</option>
                    <option value="custom">Custom</option>
                    <option value="none">None</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 mb-1">Default Footer</label>
                <select wire:model="defaultFooterMode" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="global">Global Footer</option>
                    <option value="custom">Custom</option>
                    <option value="none">None</option>
                </select>
            </div>
        </div>

        <div class="pt-2">
            <button wire:click="save" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Save Settings
            </button>
        </div>
    </div>
</div>
