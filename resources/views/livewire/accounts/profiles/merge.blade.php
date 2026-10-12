{{-- Review & merge duplicates — same design language as the profile pages. --}}
<div class="box-border flex w-full max-w-[1400px] flex-col gap-[18px] px-[28px] pb-[40px] pt-[20px]">
    <a href="{{ route('accounts.profiles.index') }}" wire:navigate class="flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Master Profiles</a>

    <x-accounts.page-head title="Review & merge" subtitle="একই phone number-এ একাধিক profile। Primary বেছে নিন — বাকিগুলোর role, ledger, কাগজ আর notes primary-তে যাবে, তারপর ওগুলো মুছে যাবে।" />

    @forelse ($this->groups as $g)
        <section wire:key="g-{{ $g['phone'] }}" class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[12px]">
                <h2 class="m-0 text-[15px] font-bold">{{ $g['phone'] }} <span class="font-normal text-[#6b7280]">· {{ count($g['profiles']) }} profiles</span></h2>
                @if ($confirming === $g['phone'])
                    <div class="flex items-center gap-[8px] text-[13px]">
                        <span class="text-[#b45309]">নিশ্চিত? এটা ফেরানো যাবে না।</span>
                        <button type="button" wire:click="$set('confirming', null)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] text-[#374151]">Cancel</button>
                        <button type="button" wire:click="merge('{{ $g['phone'] }}')" wire:loading.attr="disabled" class="rounded-[8px] bg-[#4f46e5] px-[14px] py-[7px] font-semibold text-white">Merge now</button>
                    </div>
                @else
                    <button type="button" wire:click="$set('confirming', '{{ $g['phone'] }}')" class="rounded-[8px] bg-[#4f46e5] px-[14px] py-[8px] text-[13px] font-semibold text-white">Merge into primary</button>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[760px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="px-[18px] py-[10px] font-semibold">Primary</th>
                            <th class="px-[8px] py-[10px] font-semibold">Profile</th>
                            <th class="px-[8px] py-[10px] font-semibold">Roles</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Balance</th>
                            <th class="px-[8px] py-[10px] font-semibold">Created</th>
                            <th class="px-[18px] py-[10px]"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($g['profiles'] as $row)
                            @php $p = $row['model']; $b = $row['balance']; @endphp
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="px-[18px] py-[11px]"><input type="radio" wire:model.live="primary.{{ $g['phone'] }}" value="{{ $p->id }}" class="text-[#4f46e5]" aria-label="Primary"></td>
                                <td class="px-[8px] py-[11px]"><div class="font-semibold">{{ $p->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->typeLabel() }}{{ $p->email ? ' · ' . $p->email : '' }}</div></td>
                                <td class="px-[8px] py-[11px]"><div class="flex flex-wrap gap-[4px]">@forelse ($p->roles as $r)<x-accounts.role-badge :role="$r->role" />@empty<span class="text-[#9ca3af]">—</span>@endforelse</div></td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-right"><div class="{{ \App\Services\Profiles\ProfileBalances::kindClass($b['kind']) }}">{{ $money($b['amount']) }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $b['label'] }}</div></td>
                                <td class="whitespace-nowrap px-[8px] py-[11px] text-[#6b7280]">{{ $p->created_at?->format('d M Y') }}</td>
                                <td class="px-[18px] py-[11px] text-right"><a href="{{ route('accounts.profiles.show', $p) }}" wire:navigate class="font-semibold">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">কোনো duplicate নেই।</div>
    @endforelse
</div>
