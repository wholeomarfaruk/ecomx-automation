<div x-data x-init="$store.pageName = { name: '{{ $title }}', slug: 'frontend' }">
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Coming soon</h3>
        <p class="text-sm text-gray-400">{{ $description }}</p>
    </div>
</div>
