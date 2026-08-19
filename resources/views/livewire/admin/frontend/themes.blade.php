<div x-data x-init="$store.pageName = { name: 'Themes', slug: 'frontend' }">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        @livewire('admin.theme-engine.theme-manager')
    </div>
</div>
