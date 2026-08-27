<div class="space-y-6">

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['label' => 'First Seen', 'value' => $stats['first_seen']?->format('d M Y') ?? '—', 'sub' => $stats['first_seen']?->diffForHumans()],
                ['label' => 'Last Seen', 'value' => $stats['last_seen']?->format('d M Y') ?? '—', 'sub' => $stats['last_seen']?->diffForHumans()],
                ['label' => 'Total Events', 'value' => number_format($stats['total_events'])],
                ['label' => 'Sessions', 'value' => number_format($stats['distinct_sessions'])],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</p>
                <p class="text-lg font-bold text-gray-900 mt-1">{{ $card['value'] }}</p>
                @if (!empty($card['sub']))
                    <p class="text-xs text-gray-400 mt-0.5">{{ $card['sub'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Funnel + revenue --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Funnel</h2>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
                @foreach ([
                    ['label' => 'Page Views', 'value' => $stats['page_views']],
                    ['label' => 'Product Views', 'value' => $stats['product_views']],
                    ['label' => 'Add to Cart', 'value' => $stats['add_to_cart']],
                    ['label' => 'Checkout', 'value' => $stats['checkouts']],
                    ['label' => 'Purchases', 'value' => $stats['purchases']],
                ] as $stage)
                    <div>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($stage['value']) }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $stage['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-linear-to-br from-indigo-50 to-white border border-indigo-100 rounded-2xl shadow-sm p-5">
            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">৳{{ number_format($stats['revenue'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $stats['purchases'] }} {{ Str::plural('purchase', $stats['purchases']) }}</p>
        </div>
    </div>

    {{-- Sub-tabs --}}
    <div class="flex items-center gap-1 border-b border-gray-200 overflow-x-auto">
        @foreach ([
            ['key' => 'overview', 'label' => 'Overview'],
            ['key' => 'timeline', 'label' => 'Timeline'],
            ['key' => 'attribution', 'label' => 'Attribution'],
            ['key' => 'sources', 'label' => 'Sources'],
            ['key' => 'campaigns', 'label' => 'Campaigns'],
            ['key' => 'devices', 'label' => 'Devices Activity'],
        ] as $tabItem)
            <button type="button" wire:click="$set('subTab', '{{ $tabItem['key'] }}')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition whitespace-nowrap
                    {{ $subTab === $tabItem['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ $tabItem['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    @if ($subTab === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Activity</h2>
                    <button type="button" wire:click="$set('subTab', 'timeline')" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                        View full timeline →
                    </button>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($recentActivity as $event)
                        @php
                            $badge = match ($event->event_name) {
                                'Purchase' => 'bg-emerald-100 text-emerald-700',
                                'InitiateCheckout' => 'bg-amber-100 text-amber-700',
                                'AddToCart' => 'bg-sky-100 text-sky-700',
                                'ViewContent' => 'bg-indigo-100 text-indigo-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ $badge }}">
                                    {{ $event->event_name }}
                                </span>
                                @if ($event->content_name)
                                    <span class="text-sm text-gray-600 truncate">{{ $event->content_name }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400 shrink-0">{{ $event->occurred_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-400">No events recorded yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-900">Current Attribution</h2>
                    <button type="button" wire:click="$set('subTab', 'attribution')" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                        Full history →
                    </button>
                </div>
                @if ($currentAttribution)
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">First Touch</p>
                            <p class="text-gray-800 capitalize">{{ $currentAttribution->first_touch_source ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Last Touch</p>
                            <p class="text-gray-800 capitalize">{{ $currentAttribution->last_touch_source ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Campaign</p>
                            <p class="text-gray-800">{{ $currentAttribution->last_touch_campaign ?? $currentAttribution->first_touch_campaign ?? '—' }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No attribution data recorded.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Top Viewed Products</h2>
            <div class="space-y-3">
                @php $maxProduct = max(1, $topProducts->max('total') ?? 1); @endphp
                @forelse ($topProducts as $product)
                    <a href="{{ route('admin.marketing.products.journeys', ['selectedProduct' => $product->content_name]) }}" class="block group">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-700 truncate pr-2 group-hover:text-indigo-600 group-hover:underline">{{ $product->content_name }}</span>
                            <span class="text-gray-900 font-semibold shrink-0">{{ $product->total }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ round($product->total / $maxProduct * 100) }}%"></div>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No product views recorded.</p>
                @endforelse
            </div>
        </div>

        {{-- Quick links --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Quick Links</h2>
            <div class="flex flex-wrap gap-2">
                @if ($customerId)
                    <a href="{{ route('admin.marketing.journeys.detail', ['customerId' => $customerId]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Open Full Journey →
                    </a>
                @endif
                <a href="{{ route('admin.marketing.events.index', array_filter(['customerId' => $customerId])) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Open in Event Explorer
                </a>
            </div>
        </div>
    @endif

    {{-- Timeline --}}
    @if ($subTab === 'timeline')
        <div class="space-y-6">
            @forelse ($visits as $visit)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            {{ $visit['started_at']->format('d M Y, H:i') }} · {{ $visit['started_at']->diffForHumans() }}
                        </p>
                    </div>

                    <div class="relative pl-6 space-y-4 border-l-2 border-gray-100">
                        @foreach ($visit['events'] as $event)
                            @php
                                $badge = match ($event->event_name) {
                                    'Purchase' => 'bg-emerald-100 text-emerald-700',
                                    'InitiateCheckout' => 'bg-amber-100 text-amber-700',
                                    'AddToCart' => 'bg-sky-100 text-sky-700',
                                    'ViewContent' => 'bg-indigo-100 text-indigo-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <div class="relative">
                                <span class="absolute -left-[27px] top-1 w-3 h-3 rounded-full bg-white border-2 border-indigo-400"></span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ $event->event_name }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $event->occurred_at->format('H:i:s') }}</span>
                                    @if ($event->attribution?->last_touch_source)
                                        <span class="text-xs text-gray-400">via {{ $event->attribution->last_touch_source }}</span>
                                    @endif
                                    @if ($event->device)
                                        <span class="text-xs text-gray-400 font-mono">{{ Str::limit($event->device->fingerprint, 12) }}</span>
                                    @endif
                                </div>
                                @if ($event->content_name)
                                    <p class="text-sm text-gray-700 mt-1">{{ $event->content_name }}</p>
                                @endif
                                @if ($event->value)
                                    <p class="text-sm font-semibold text-gray-900 mt-0.5">৳{{ number_format($event->value, 2) }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-16 text-center">
                    <p class="text-sm font-semibold text-gray-700">No events recorded yet</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Attribution --}}
    @if ($subTab === 'attribution')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">First Touch</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Touch</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Campaign</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Captured</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($attributionHistory as $attribution)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-5 py-3 text-sm text-gray-700 capitalize">{{ $attribution->first_touch_source ?? '—' }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700 capitalize">{{ $attribution->last_touch_source ?? '—' }}</td>
                                <td class="px-3 py-3 text-sm text-gray-600">{{ $attribution->last_touch_campaign ?? $attribution->first_touch_campaign ?? '—' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500">{{ $attribution->last_touch_at?->diffForHumans() ?? $attribution->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No attribution data recorded for this profile.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Sources --}}
    @if ($subTab === 'sources')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Events</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($sources as $row)
                            <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                                onclick="window.location.href='{{ route('admin.marketing.events.index', array_filter(['source' => $row->source, 'customerId' => $customerId, 'deviceId' => $customerId ? null : (empty($deviceIds) ? null : $deviceIds[0])])) }}'">
                                <td class="px-5 py-3 text-sm font-medium text-gray-800 capitalize">{{ $row->source }}</td>
                                <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($row->events) }}</td>
                                <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-16 text-center text-sm text-gray-500">No source data recorded for this profile.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Campaigns --}}
    @if ($subTab === 'campaigns')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Campaign</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Events</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($campaigns as $row)
                            <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                                onclick="window.location.href='{{ route('admin.marketing.events.index', array_filter(['campaign' => $row->utm_campaign, 'customerId' => $customerId, 'deviceId' => $customerId ? null : (empty($deviceIds) ? null : $deviceIds[0])])) }}'">
                                <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ $row->utm_campaign }}</td>
                                <td class="px-3 py-3 text-sm text-gray-600 capitalize">{{ $row->utm_source ?? '—' }}</td>
                                <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($row->events) }}</td>
                                <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No campaign data recorded for this profile.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Devices Activity --}}
    @if ($subTab === 'devices')
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/40">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fingerprint</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type / OS / Browser</th>
                            <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Tracked Events</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Tracked</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($deviceActivity as $device)
                            <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                                onclick="window.location.href='{{ route('admin.marketing.journeys.detail', ['deviceId' => $device->id]) }}'">
                                <td class="px-5 py-3 text-xs font-mono text-gray-600">{{ Str::limit($device->fingerprint, 20) }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-sm text-gray-700">{{ $device->device_type ?? '—' }}</span>
                                    <span class="block text-xs text-gray-400">{{ $device->operating_system }} · {{ $device->browser }}</span>
                                </td>
                                <td class="px-3 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($device->tracked_events) }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $device->last_tracked_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No tracked devices for this profile.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
