<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Source Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">Traffic quality compared across every source — Meta, Google, TikTok, Direct.</p>
        </div>
        @include('livewire.admin.marketing.partials.date-range-picker')
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($sources as $source)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 capitalize">{{ $source['source'] }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $source['conversion_rate'] >= 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $source['conversion_rate'] }}% CVR
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Visitors</p>
                        <p class="font-semibold text-gray-900">{{ number_format($source['visitors']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Returning</p>
                        <p class="font-semibold text-gray-900">{{ number_format($source['returning']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Product Views</p>
                        <p class="font-semibold text-gray-900">{{ number_format($source['product_views']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Add to Cart</p>
                        <p class="font-semibold text-gray-900">{{ number_format($source['add_to_cart']) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Checkout Abandon</p>
                        <p class="font-semibold text-gray-900">{{ number_format(max(0, $source['checkout_abandon'])) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Purchases</p>
                        <p class="font-semibold text-gray-900">{{ number_format($source['purchases']) }}</p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400">Revenue</p>
                        <p class="text-lg font-bold text-gray-900">৳{{ number_format($source['revenue'], 2) }}</p>
                    </div>
                    @if ($source['source'] !== 'direct')
                        <a href="{{ route('admin.marketing.events.index', ['source' => $source['source']]) }}"
                            class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                            View Events
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-200 p-16 text-center">
                <p class="text-sm font-semibold text-gray-700">No traffic in this range</p>
            </div>
        @endforelse
    </div>
</div>
