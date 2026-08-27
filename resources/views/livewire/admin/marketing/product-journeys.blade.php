<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Product Journeys</h1>
            <p class="text-sm text-gray-500 mt-1">What visitors who viewed a product go on to view next, and when they return.</p>
        </div>
        <a href="{{ route('admin.marketing.products.performance') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium shrink-0">
            ← Product Performance
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <label class="block text-xs font-medium text-gray-500 mb-1">Select a product</label>
        <select wire:model.live="selectedProduct" class="w-full max-w-md rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Choose a product…</option>
            @foreach ($products as $product)
                <option value="{{ $product }}">{{ $product }}</option>
            @endforeach
        </select>
    </div>

    @if ($selectedProduct !== '')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Viewed next</h2>
                <div class="space-y-3">
                    @forelse ($nextViewed as $row)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-700">{{ $row['name'] }}</span>
                                <span class="text-gray-900 font-semibold">{{ $row['percentage'] }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500" style="width: {{ $row['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No follow-on views recorded for this product.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Return window</h2>
                <div class="space-y-3">
                    @php
                        $labels = ['same_day' => 'Same day', '1_3' => '1–3 days', '4_7' => '4–7 days', '8_30' => '8–30 days', 'never' => 'Never returned'];
                        $max = max(1, $returnWindow->max());
                    @endphp
                    @foreach ($labels as $key => $label)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-700">{{ $label }}</span>
                                <span class="text-gray-900 font-semibold">{{ number_format($returnWindow[$key] ?? 0) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $key === 'never' ? 'bg-gray-300' : 'bg-emerald-500' }}"
                                    style="width: {{ round(($returnWindow[$key] ?? 0) / $max * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-16 text-center">
            <p class="text-sm font-semibold text-gray-700">Select a product above to see its journey data</p>
        </div>
    @endif
</div>
