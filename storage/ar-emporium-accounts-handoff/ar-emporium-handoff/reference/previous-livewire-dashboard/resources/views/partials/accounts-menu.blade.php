{{--
    Accounts sidebar group — drop into your admin sidebar (dark #0f172a).
    Uses Alpine for collapse. Route names are guesses: links fall back to "#"
    until a matching route exists, so rename to match your routes/web.php.
--}}
@php
    $link = fn (string $name) => Route::has($name) ? route($name) : '#';

    $items = [
        ['route' => 'admin.accounts.dashboard',    'label' => 'Dashboard'],
        ['route' => 'admin.accounts.transactions', 'label' => 'Transactions'],
        ['route' => 'admin.accounts.posting',      'label' => 'Posting Pending', 'badge' => $pendingCount ?? null],
        ['route' => 'admin.accounts.cash-bank',    'label' => 'Cash & Bank'],
        ['route' => 'admin.accounts.receivables',  'label' => 'Receivables (Due)'],
        ['route' => 'admin.accounts.payables',     'label' => 'Payables'],
        ['route' => 'admin.accounts.loans',        'label' => 'Loans & EMI'],
        ['route' => 'admin.accounts.fixed-assets', 'label' => 'Fixed Assets'],
        ['route' => 'admin.accounts.expenses',     'label' => 'Expenses'],
        ['route' => 'admin.accounts.reports',      'label' => 'Reports'],
        ['route' => 'admin.accounts.settings',     'label' => 'Settings'],
    ];
@endphp

<div x-data="{ open: {{ request()->routeIs('admin.accounts.*') ? 'true' : 'false' }} }" class="flex flex-col gap-0.5">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-slate-300 hover:bg-slate-800 hover:text-white">
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 9.5c-.5-1-1.6-1.5-3-1.5-1.7 0-3 .9-3 2s1.3 1.7 3 2 3 .9 3 2-1.3 2-3 2c-1.4 0-2.5-.5-3-1.5M12 6v2M12 16v2"/></svg>
        <span class="flex-1">Accounts</span>
        <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-collapse class="ml-[18px] flex flex-col gap-0.5 border-l border-slate-700 pl-3">
        @foreach ($items as $item)
            @if (! empty($item['children']))
                <div x-data="{ sub: {{ request()->routeIs($item['route']) ? 'true' : 'false' }} }">
                    <button type="button" @click="sub = !sub" :aria-expanded="sub"
                            class="flex w-full items-center justify-between rounded-md px-2 py-1.5 text-left text-[13.5px] text-slate-300 hover:text-white">
                        {{ $item['label'] }}
                        <svg class="h-3.5 w-3.5 transition" :class="sub && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="sub" x-collapse class="flex flex-col pl-3">
                        @foreach ($item['children'] as $child)
                            <a href="{{ $link($child['route']) }}" wire:navigate
                               @class([
                                   'rounded-md px-2 py-1.5 text-[13px]',
                                   'font-semibold text-white' => request()->routeIs($child['route']),
                                   'text-slate-400 hover:text-white' => ! request()->routeIs($child['route']),
                               ])>{{ $child['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ $link($item['route']) }}" wire:navigate
                   @class([
                       'flex items-center justify-between rounded-md px-2 py-1.5 text-[13.5px]',
                       'bg-slate-800 font-semibold text-white' => request()->routeIs($item['route']),
                       'text-slate-300 hover:text-white' => ! request()->routeIs($item['route']),
                   ])>
                    {{ $item['label'] }}
                    @if (! empty($item['badge']))
                        <span class="rounded-full bg-amber-500 px-2 text-[11px] font-bold text-gray-900">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endif
        @endforeach
    </div>
</div>
