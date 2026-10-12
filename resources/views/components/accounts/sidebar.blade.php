{{--
    Accounts Panel sidebar — design: Sidebar.dc.html (profile pages) and the
    inline sidebar in Main.dc.html (dashboard). `active` is a profile key
    (profiles, customers, …) or "dashboard". Accounts items that don't have
    an Accounts Panel page yet open the existing Admin › Accounts page, only
    for users who also hold the admin panel.
--}}
@props(['active' => 'profiles'])

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'), 'general') ?: config('app.name');
    $logo     = \App\Models\Setting::get('site_logo_white', null, 'general');
    $logoUrl  = $logo ? file_path($logo) : null;
    $hasAdmin = auth()->user()?->hasPanel('admin') ?? false;
    $pending  = \App\Models\JournalEntry::where('status', \App\Enums\Accounts\JournalEntryStatus::DRAFT)->count();
    $tabs     = \App\Services\Profiles\ProfileCounts::tabs();
    $inProfiles = $active !== 'dashboard';

    $href = function (?string $name) use ($hasAdmin) {
        if (! $name || ! Route::has($name)) {
            return '#';
        }

        return str_starts_with($name, 'accounts.') || $hasAdmin ? route($name) : '#';
    };

    $item  = 'flex items-center gap-[10px] rounded-[8px] px-[10px] py-[9px] text-[#cbd5e1] hover:text-white';
    $on    = 'flex items-center gap-[10px] rounded-[8px] bg-[#1e293b] px-[10px] py-[9px] font-semibold text-white';
    $label = 'px-[8px] pb-[6px] text-[11px] uppercase tracking-[0.08em] text-[#64748b]';
@endphp

<nav aria-label="Main" class="box-border flex min-h-full w-full flex-col gap-[4px] bg-[#0f172a] px-[14px] py-[18px] text-[14px] text-[#cbd5e1]">
    <div class="flex items-center gap-[10px] px-[6px] pb-[22px] pt-[4px]">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="h-[34px] w-[34px] rounded-[9px] object-contain">
        @else
            <div class="flex h-[34px] w-[34px] items-center justify-center rounded-[9px] bg-[#4f46e5] font-bold text-white">{{ strtoupper(mb_substr($siteName, 0, 1)) }}</div>
        @endif
        <div class="text-[16px] font-bold text-white">{{ $siteName }}</div>
    </div>

    <div class="{{ $label }} pt-[8px]">Accounts</div>
    <a href="{{ route('accounts.dashboard') }}" wire:navigate class="{{ $active === 'dashboard' ? $on : $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="16" width="7" height="5" rx="1.5"></rect></svg>
        Dashboard</a>

    {{-- All Profiles: open only while on a profile page; same look everywhere. --}}
    <div x-data="{ open: @js($inProfiles) }" class="flex flex-col gap-[4px]">
        <button type="button" @click="open = !open" :aria-expanded="open"
                @class(['flex w-full items-center gap-[10px] rounded-[8px] border-0 bg-transparent px-[10px] py-[9px] text-left text-[14px]', 'font-semibold text-white' => $inProfiles, 'text-[#cbd5e1] hover:text-white' => ! $inProfiles])>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"></circle><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5"></path><path d="M16 4.5a3.5 3.5 0 0 1 0 7"></path><path d="M18.5 14.8c1.6.7 2.6 2.4 3 5.2"></path></svg>
            <span class="flex-1">All Profiles</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="transition" :class="open ? '' : '-rotate-90'"><path d="m6 9 6 6 6-6"></path></svg>
        </button>
        <div x-show="open" x-collapse @unless ($inProfiles) x-cloak @endunless
             class="ml-[18px] flex flex-col gap-[2px] border-l border-[#334155] pl-[12px] text-[13px]">
            @foreach ($tabs as $t)
                <a href="{{ route($t['route']) }}" wire:navigate
                   @class([
                       'flex justify-between rounded-[6px] px-[8px] py-[6px]',
                       'bg-[#1e293b] font-semibold text-white' => $active === $t['key'],
                       'text-[#94a3b8] hover:text-white' => $active !== $t['key'],
                   ])>
                    <span>{{ $t['label'] }}</span><span class="text-[12px] text-[#64748b]">{{ number_format($t['count']) }}</span>
                </a>
            @endforeach
        </div>
    </div>
    <a href="{{ $href('admin.accounts.transactions') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4 3 8l4 4"></path><path d="M3 8h14"></path><path d="m17 20 4-4-4-4"></path><path d="M21 16H7"></path></svg>
        Transactions</a>
    <a href="{{ route('accounts.dashboard') }}#posting-pending" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
        <span class="flex-1">Posting Pending</span>
        @if ($pending)
            <span class="rounded-[10px] bg-[#f59e0b] px-[8px] py-[1px] text-[11px] font-bold text-[#1f2937]">{{ $pending }}</span>
        @endif
    </a>
    <a href="{{ $href('admin.accounts.cash-accounts.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="6" width="19" height="13" rx="2"></rect><path d="M2.5 10h19"></path><path d="M6 15h4"></path></svg>
        Cash &amp; Bank</a>
    <a href="{{ $href('admin.accounts.receivables.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 7 7 17"></path><path d="M7 8v9h9"></path></svg>
        Receivables (Due)</a>
    <a href="{{ $href('admin.accounts.payables.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"></path><path d="M8 7h9v9"></path></svg>
        Payables</a>
    <a href="{{ route('accounts.profiles.lenders') }}" wire:navigate class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10 12 4l9 6"></path><path d="M5 10v8"></path><path d="M10 10v8"></path><path d="M14 10v8"></path><path d="M19 10v8"></path><path d="M3 20h18"></path></svg>
        Loans &amp; EMI</a>
    <a href="{{ $href('admin.accounts.fixed-assets.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M8 20h8"></path><path d="M12 16v4"></path></svg>
        Fixed Assets</a>
    <a href="{{ $href('admin.accounts.expenses.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"></path><path d="M9 12h7"></path><path d="M9 16h7"></path></svg>
        Expenses</a>
    <a href="{{ $href('admin.accounts.reports.index') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"></path><path d="M10 20V4"></path><path d="M16 20v-7"></path><path d="M21 20H3"></path></svg>
        Reports</a>
    <a href="{{ $href('admin.accounts.settings.chart-of-accounts') }}" class="{{ $item }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"></path></svg>
        Settings</a>

    <div class="{{ $label }} pt-[18px]">Operations</div>
    <a href="{{ $href('admin.purchase.orders') }}" class="{{ $item }}">Purchase</a>
    <a href="{{ $href('admin.inventory.stock') }}" class="{{ $item }}">Inventory</a>

    @if ($hasAdmin)
        <a href="{{ route('admin.dashboard') }}" class="{{ $item }} mt-auto text-[13px] text-[#94a3b8]">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            Back to Admin</a>
    @endif
</nav>
