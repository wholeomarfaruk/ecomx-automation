{{-- Profile module tabs — design: ProfileTabs.dc.html, shown inside the page body
     (the shared Accounts Panel header already carries the bell and avatar). --}}
@props(['active' => 'profiles'])

<nav aria-label="Profile modules" class="acc-no-print flex max-w-full gap-[4px] overflow-x-auto rounded-[14px] border border-[#e5e7eb] bg-white px-[14px] text-[14px]">
    @foreach (\App\Services\Profiles\ProfileCounts::tabs() as $t)
        @php $on = $active === $t['key']; @endphp
        <a href="{{ route($t['route']) }}" wire:navigate
           @class([
               'flex items-center gap-[8px] whitespace-nowrap border-b-[3px] px-[14px] pb-[13px] pt-[15px] no-underline',
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
