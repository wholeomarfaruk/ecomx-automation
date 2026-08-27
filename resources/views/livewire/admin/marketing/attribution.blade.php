<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Attribution</h1>
        <p class="text-sm text-gray-500 mt-1">First-touch vs last-touch revenue, and the conversion paths in between.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">First-Touch Revenue</h2>
            <p class="text-xs text-gray-400 mb-4">The source that originally acquired the customer.</p>
            <div class="space-y-3">
                @php $maxFirst = max(1, $firstTouch->max('revenue') ?? 1); @endphp
                @forelse ($firstTouch as $row)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 capitalize">{{ $row->source }}</span>
                            <span class="text-gray-900 font-semibold">৳{{ number_format($row->revenue, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ round($row->revenue / $maxFirst * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No attributed purchases yet.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Last-Touch Revenue</h2>
            <p class="text-xs text-gray-400 mb-4">The source that closed the sale.</p>
            <div class="space-y-3">
                @php $maxLast = max(1, $lastTouch->max('revenue') ?? 1); @endphp
                @forelse ($lastTouch as $row)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 capitalize">{{ $row->source }}</span>
                            <span class="text-gray-900 font-semibold">৳{{ number_format($row->revenue, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ round($row->revenue / $maxLast * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No attributed purchases yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Top Conversion Paths</h2>
            <p class="text-xs text-gray-400 mt-0.5">First touch → Last touch, for purchases where they differ.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Path</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Purchases</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($paths as $path)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.marketing.events.index', ['source' => $path->last_touch_source, 'eventName' => 'Purchase']) }}'">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="capitalize text-gray-700">{{ $path->first_touch_source }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                    <span class="capitalize font-medium text-gray-900">{{ $path->last_touch_source }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($path->total) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($path->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-16 text-center text-sm text-gray-500">No multi-touch conversion paths recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
