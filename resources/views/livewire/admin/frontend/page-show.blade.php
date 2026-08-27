<div x-data x-init="$store.pageName = { name: '{{ $meta['label'] ?? ucfirst($page) }}', slug: 'frontend' }">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <p class="text-sm text-gray-500 mb-6">
            Toggle sections active/inactive and drag to reorder how they render on the storefront.
        </p>

        @livewire('admin.theme-engine.page-section-manager', ['page' => $page])
    </div>
</div>
