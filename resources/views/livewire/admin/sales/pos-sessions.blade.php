<div x-data x-init="$store.pageName = { name: 'POS Sessions', slug: 'sales-pos-sessions' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-2 gap-3 flex-1 max-w-sm">
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Total Sessions</p>
                <p class="text-xl font-semibold text-gray-800 mt-0.5">{{ $totalCount }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3">
                <p class="text-xs text-gray-400">Currently Open</p>
                <p class="text-xl font-semibold text-emerald-600 mt-0.5">{{ $openCount }}</p>
            </div>
        </div>
        <a href="{{ route('admin.sales.pos.sessions.open') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Open Session
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <div class="relative flex-1 min-w-[200px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by cashier name…"
                    class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <select wire:model.live="filterRegister"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Registers</option>
                @foreach($registers as $register)
                    <option value="{{ $register->id }}">{{ $register->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus"
                class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Session</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Register</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cashier</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Sales</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Opening</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Closing</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer"
                            onclick="window.location.href='{{ route('admin.sales.pos.sessions.show', $session->id) }}'">
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-gray-800">#{{ $session->id }}</span>
                                <span class="block text-xs text-gray-400">{{ $session->opened_at->format('d M, Y H:i') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $session->register->name ?? '—' }}</span>
                                <span class="block text-xs text-gray-400">{{ $session->register->branch->name ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="text-sm text-gray-600">{{ $session->user->name ?? '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm text-gray-600">{{ $session->sales_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm text-gray-700">{{ number_format($session->opening_cash, 2) }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="text-sm text-gray-700">{{ $session->closing_cash !== null ? number_format($session->closing_cash, 2) : '—' }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $session->status->badgeClass() }}">
                                    {{ $session->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-700">No sessions found</p>
                                        <p class="text-xs text-gray-400 mt-0.5">Open a session to start taking POS sales</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sessions->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</div>
