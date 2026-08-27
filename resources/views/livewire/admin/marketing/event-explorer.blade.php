<div class="space-y-6" x-data="{ detail: null }">

    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold text-gray-900">Event Explorer</h1>
        <p class="text-sm text-gray-500">{{ number_format($totalCount) }} canonical marketing events recorded in total.</p>
    </div>

    {{-- Deep-link banner: shown when arriving from another marketing screen with a device/customer/campaign filter already applied --}}
    @if ($deviceId || $customerId || $campaign !== '')
        <div class="flex items-center justify-between gap-3 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3">
            <p class="text-sm text-indigo-700">
                Showing events filtered by
                @if ($deviceId) <strong>device #{{ $deviceId }}</strong> @endif
                @if ($customerId) <strong>customer #{{ $customerId }}</strong> @endif
                @if ($campaign !== '') <strong>campaign "{{ $campaign }}"</strong> @endif
                @if ($deviceId || $customerId)
                    — <a href="{{ route('admin.marketing.journeys.detail', array_filter(['deviceId' => $deviceId, 'customerId' => $customerId])) }}" class="underline hover:no-underline">view full profile</a>
                @endif
            </p>
            <button wire:click="resetFilters" type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 shrink-0">Clear</button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Event ID, product, page, customer…"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
                <select wire:model.live="eventName" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All events</option>
                    @foreach ($eventNames as $name)
                        <option value="{{ $name->value }}">{{ $name->value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Source</label>
                <select wire:model.live="source" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All sources</option>
                    @foreach ($sources as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
                        class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-36 cursor-pointer">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <input wire:model.live="dateTo" type="text" placeholder="To" readonly
                        class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-36 cursor-pointer">
                </div>
            </div>
            @if ($search || $eventName || $source || $dateFrom || $dateTo || $campaign !== '' || $deviceId || $customerId)
                <button wire:click="resetFilters" type="button" class="text-sm text-gray-500 hover:text-gray-700 transition pb-2">Clear</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Time</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Event</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device / Customer</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Source</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Value</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($events as $event)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            @click="detail = {{ $event->toJson() }}">
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-700">{{ $event->occurred_at->format('d M, H:i:s') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $badge = match ($event->event_name) {
                                        'Purchase' => 'bg-emerald-100 text-emerald-700',
                                        'InitiateCheckout' => 'bg-amber-100 text-amber-700',
                                        'AddToCart' => 'bg-sky-100 text-sky-700',
                                        'ViewContent' => 'bg-indigo-100 text-indigo-700',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">
                                    {{ $event->event_name }}
                                </span>
                                @if ($event->content_name)
                                    <span class="block text-xs text-gray-400 mt-1">{{ $event->content_name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($event->customer_id || $event->device_id)
                                    <a href="{{ route('admin.marketing.journeys.detail', array_filter(['deviceId' => $event->device_id, 'customerId' => $event->customer_id])) }}"
                                        @click.stop
                                        class="block text-sm text-gray-600 hover:text-indigo-600 hover:underline">
                                        {{ $event->customer?->full_name ?? 'Anonymous' }}
                                    </a>
                                @else
                                    <span class="block text-sm text-gray-600">Anonymous</span>
                                @endif
                                <span class="block text-xs text-gray-400 font-mono">{{ $event->device?->fingerprint ? Str::limit($event->device->fingerprint, 16) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500 capitalize">{{ $event->utm_source ?? $event->source ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm font-medium text-gray-800">{{ $event->value ? number_format($event->value, 2) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">No events found</p>
                                <p class="text-xs text-gray-400 mt-0.5">Try adjusting the filters above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($events->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $events->links() }}
            </div>
        @endif
    </div>

    {{-- Detail drawer --}}
    <div x-cloak x-show="detail" x-transition.opacity class="fixed inset-0 bg-gray-900/40 z-40" @click="detail = null"></div>
    <div x-cloak x-show="detail" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 h-screen w-full max-w-md bg-white shadow-2xl z-50 overflow-y-auto">
        <template x-if="detail">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900" x-text="detail.event_name"></h2>
                    <button @click="detail = null" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <template x-if="detail.device_id || detail.customer_id">
                    <a :href="'{{ route('admin.marketing.journeys.detail') }}?' + (detail.device_id ? 'deviceId=' + detail.device_id : '') + (detail.customer_id ? 'customerId=' + detail.customer_id : '')"
                        class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        View full journey →
                    </a>
                </template>
                <pre class="text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-x-auto" x-text="JSON.stringify(detail, null, 2)"></pre>
            </div>
        </template>
    </div>
</div>
