{{-- Top bar of every profile screen — design: ProfileTabs.dc.html --}}
@props(['active' => 'profiles'])

@php
    $user = auth()->user();
    $initials = \App\Models\MasterProfile::initialsOf($user?->name) ?: 'U';
@endphp

<div class="box-border flex w-full flex-wrap items-center justify-between gap-[12px] border-b border-[#e5e7eb] bg-white px-[28px] text-[14px] text-[#111827]">
    <nav aria-label="Profile modules" class="flex max-w-full gap-[4px] overflow-x-auto">
        @foreach (\App\Services\Profiles\ProfileCounts::tabs() as $t)
            @php $on = $active === $t['key']; @endphp
            <a href="{{ route($t['route']) }}" wire:navigate
               @class([
                   'flex items-center gap-[8px] whitespace-nowrap border-b-[3px] px-[14px] pb-[18px] pt-[20px] no-underline',
                   'border-[#4f46e5] font-bold !text-[#312e81]' => $on,
                   'border-transparent font-medium !text-[#4b5563] hover:!text-[#111827]' => ! $on,
               ])>
                <span>{{ $t['label'] }}</span>
                <span @class([
                    'rounded-[10px] px-[8px] py-[1px] text-[11.5px] font-bold',
                    'bg-[#e0e7ff] text-[#3730a3]' => $on,
                    'bg-[#f3f4f6] text-[#4b5563]' => ! $on,
                ])>{{ number_format($t['count']) }}</span>
            </a>
        @endforeach
    </nav>
    <div class="flex items-center gap-[12px] py-[10px]">
        @livewire('admin.topbar.notifications', ['variant' => 'accounts'])
        <div x-data="{ open: false }" class="relative">
            <button type="button" @click="open = !open" @click.outside="open = false" aria-label="Account menu"
                    class="flex h-[34px] w-[34px] items-center justify-center rounded-full bg-[#0f172a] text-[12px] font-bold text-white">{{ $initials }}</button>
            <div x-cloak x-show="open" x-transition
                 class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-[10px] border border-[#e5e7eb] bg-white shadow-lg">
                <div class="border-b border-[#f0f1f4] px-4 py-3">
                    <p class="truncate text-[13px] font-semibold text-[#111827]">{{ $user?->name }}</p>
                    <p class="truncate text-[12px] text-[#6b7280]">{{ $user?->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 text-left text-[13px] text-[#b91c1c] hover:bg-[#f9fafb]">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>
