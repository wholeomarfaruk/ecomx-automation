{{-- Import profiles — same design language as the profile pages. --}}
@php
    $sel = 'w-full text-[13px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $preview = $this->preview;
    $badge = ['new' => 'bg-[#d1fae5] text-[#065f46]', 'role' => 'bg-[#e0e7ff] text-[#3730a3]', 'skip' => 'bg-[#f3f4f6] text-[#6b7280]'];
@endphp

<div class="box-border flex w-full max-w-[1100px] flex-col gap-[16px] px-[28px] pb-[40px] pt-[20px]">
    <a href="{{ route('accounts.profiles.index') }}" wire:navigate class="flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Master Profiles</a>

    <x-accounts.page-head title="Import (Excel)" subtitle="Excel (.xlsx) বা CSV — প্রথম row-এ column-এর নাম। একই mobile number থাকলে নতুন profile না বানিয়ে role যোগ হবে।" />

    @if ($result)
        <div class="rounded-[12px] border border-[#a7f3d0] bg-[#ecfdf5] px-[16px] py-[12px] text-[13.5px] text-[#065f46]">
            Import শেষ: <b>{{ $result['new'] }}</b> নতুন profile, <b>{{ $result['role'] }}</b>টিতে role যোগ, <b>{{ $result['skip'] }}</b> skip।
            <a href="{{ route('accounts.profiles.index') }}" wire:navigate class="ml-2 font-semibold">Master Profiles দেখুন</a>
        </div>
    @endif

    <section class="flex flex-col gap-[14px] rounded-[14px] border border-[#e5e7eb] bg-white px-[20px] py-[18px]">
        <h2 class="m-0 text-[15px] font-bold">১. File দিন</h2>
        <div class="flex flex-wrap items-end gap-[14px]">
            <div class="flex flex-col gap-[6px]">
                <label class="text-[13px] font-semibold">Role (সবগুলো এই role পাবে)</label>
                <select wire:model.live="role" class="{{ $sel }} w-[200px]">
                    @foreach (\App\Models\MasterProfile::ROLES as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
                </select>
            </div>
            <div x-data class="flex flex-col gap-[6px]">
                <label class="text-[13px] font-semibold">File</label>
                <input type="file" x-ref="f" wire:model="file" accept=".xlsx,.csv" class="hidden">
                <button type="button" @click="$refs.f.click()" class="rounded-[8px] border border-[#c7d2fe] bg-white px-[14px] py-[9px] text-[13px] font-semibold text-[#4338ca]">Choose file</button>
            </div>
            <span wire:loading wire:target="file" class="text-[12.5px] text-[#6b7280]">Reading…</span>
            @if ($data)<span class="text-[13px] text-[#374151]">{{ number_format(count($data)) }} rows found</span>@endif
        </div>
        @error('file') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
    </section>

    @if ($headers)
        <section class="flex flex-col gap-[14px] rounded-[14px] border border-[#e5e7eb] bg-white px-[20px] py-[18px]">
            <h2 class="m-0 text-[15px] font-bold">২. Column মেলান</h2>
            <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
                @foreach ($this::FIELDS as $field => $label)
                    <div class="flex flex-col gap-[6px]">
                        <label class="text-[13px] font-semibold">{{ $label }}</label>
                        <select wire:model.live="map.{{ $field }}" class="{{ $sel }}">
                            <option value="">— skip —</option>
                            @foreach ($headers as $i => $h)<option value="{{ $i }}">{{ $h ?: 'Column ' . ($i + 1) }}</option>@endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            @error('map.name') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            @error('map.phone') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
        </section>

        <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-[#f0f1f4] px-[18px] py-[14px]">
                <h2 class="m-0 text-[15px] font-bold">৩. Preview</h2>
                @if ($preview)
                    @php $c = collect($preview)->countBy('status'); @endphp
                    <span class="text-[13px] text-[#4b5563]">{{ $c['new'] ?? 0 }} new · {{ $c['role'] ?? 0 }} add role · {{ $c['skip'] ?? 0 }} skip</span>
                @endif
            </div>
            <div class="max-h-[420px] overflow-auto">
                <table class="min-w-[640px] text-[13px]">
                    <thead class="sticky top-0 bg-[#f9fafb]"><tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="px-[18px] py-[10px] font-semibold">Row</th><th class="px-[8px] py-[10px] font-semibold">Name</th><th class="px-[8px] py-[10px] font-semibold">Mobile</th><th class="px-[18px] py-[10px] font-semibold">Result</th></tr></thead>
                    <tbody>
                        @forelse (array_slice($preview, 0, 200) as $r)
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="px-[18px] py-[9px] text-[#6b7280]">{{ $r['row'] }}</td>
                                <td class="px-[8px] py-[9px] font-semibold">{{ $r['name'] ?: '—' }}</td>
                                <td class="px-[8px] py-[9px]">{{ $r['phone'] ?: '—' }}</td>
                                <td class="px-[18px] py-[9px]"><span class="rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $badge[$r['status']] }}">{{ $r['note'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-[18px] py-[20px] text-center text-[#6b7280]">Name আর Mobile column বেছে নিলে preview দেখাবে।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end gap-[10px] border-t border-[#f0f1f4] bg-[#f9fafb] px-[18px] py-[12px]">
                <button type="button" wire:click="import" wire:loading.attr="disabled" @disabled(! $preview) class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white disabled:opacity-50">Import</button>
            </div>
        </section>
    @endif
</div>
