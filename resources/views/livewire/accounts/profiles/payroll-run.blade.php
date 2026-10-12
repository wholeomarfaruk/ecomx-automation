{{-- Payroll run — same design language as the profile pages. --}}
@php $run = $this->run; @endphp

<div class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="employees" />
    <a href="{{ route('accounts.profiles.employees') }}" wire:navigate class="flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Employees</a>

    <x-accounts.page-head :title="$label . ' payroll'" :subtitle="$run ? 'Due ' . $run->due_on?->format('d M Y') . ' · posted as ' . ($run->journal_entry_id ? \App\Models\JournalEntry::whereKey($run->journal_entry_id)->value('entry_number') : '—') : 'এই মাসের payroll এখনো তৈরি হয়নি।'">
        @if ($run)
            <button type="button" wire:click="editRun" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Edit bonus · due date</button>
        @endif
        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Print</button>
    </x-accounts.page-head>

    @if ($run)
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
            <x-accounts.stat label="Gross" :value="$money($run->gross)" :sub="$run->items->count() . ' employees'" />
            <x-accounts.stat label="Bonus" :value="$money($run->bonus)" :sub="$run->bonus_note ?: '—'" />
            <x-accounts.stat label="Advance কাটা" :value="$money($run->advance_deducted)" color="#4338ca" sub="অগ্রিম থেকে আদায়" />
            <x-accounts.stat label="Net · দিতে হবে" :value="$money($run->net)" :sub="$money($run->paid) . ' paid · ' . $money($run->unpaid()) . ' বাকি'" :color="$run->unpaid() > 0.5 ? '#b91c1c' : '#047857'" />
        </div>

        <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-[860px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="px-[18px] py-[10px] font-semibold">Employee</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Salary</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Bonus</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Advance কাটা</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Net</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Paid</th>
                            <th class="px-[8px] py-[10px] font-semibold">Status</th>
                            <th class="acc-no-print px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($run->items as $item)
                            <tr wire:key="pi-{{ $item->id }}" class="border-t border-[#f0f1f4]">
                                <td class="px-[18px] py-[11px]"><div class="font-semibold">{{ $item->profile?->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $item->profile?->code }} · {{ $item->profile?->roleSettings('employee')['designation'] ?? '' }}</div></td>
                                <td class="px-[8px] py-[11px] text-right">{{ $money($item->salary) }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ (float) $item->bonus > 0 ? $money($item->bonus) : '—' }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ (float) $item->advance_deduction > 0 ? $money($item->advance_deduction) : '—' }}</td>
                                <td class="px-[8px] py-[11px] text-right font-semibold">{{ $money($item->net) }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ $money($item->paid_amount) }}</td>
                                <td class="px-[8px] py-[11px]">
                                    @if ($item->unpaid() > 0.5)
                                        <span class="rounded-[6px] bg-[#fef3c7] px-[8px] py-[3px] text-[12px] font-semibold text-[#92400e]">{{ $money($item->unpaid()) }} বাকি</span>
                                    @else
                                        <span class="rounded-[6px] bg-[#d1fae5] px-[8px] py-[3px] text-[12px] font-semibold text-[#065f46]">Paid</span>
                                    @endif
                                </td>
                                <td class="acc-no-print whitespace-nowrap px-[18px] py-[11px] text-right">
                                    <a href="#" wire:click.prevent="editItem({{ $item->id }})">Edit</a>
                                    @if ($item->unpaid() > 0.5)
                                        <span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="openVoucher('salary', {{ $item->master_profile_id }})" class="font-semibold">Pay</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="rounded-[14px] border border-dashed border-[#c7d2fe] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">
            {{ $label }} payroll তৈরি হয়নি। <a href="{{ route('accounts.profiles.employees') }}" wire:navigate class="font-semibold">Employees</a> থেকে "Prepare payroll" দিন।
        </div>
    @endif

    @php $fld = 'w-full rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[14px]'; @endphp
    @if ($editing)
        <x-accounts.modal :title="'Edit payroll · ' . $editing['name']" close="$set('editing', null)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Salary (৳)</label><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="editing.salary" class="{{ $fld }}">@error('editing.salary')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bonus (৳)</label><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="editing.bonus" class="{{ $fld }}">@error('editing.bonus')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">কারণ (optional)</label><input type="text" wire:model="editing.note" placeholder="যেমন: Puja bonus, overtime" class="{{ $fld }}"></div>
            </div>
            @php $newNet = (float) $editing['salary'] + (float) ($editing['bonus'] ?: 0) - $editing['deduction']; @endphp
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[13px] text-[#374151]">
                Advance কাটা {{ $money($editing['deduction']) }} · নতুন net <b>{{ $money($newNet) }}</b> · paid {{ $money($editing['paid']) }}
                <div class="mt-[2px] text-[12px] text-[#6b7280]">পার্থক্যটা একটা adjustment journal হিসেবে post হবে (Salary expense ↔ Salary payable)।</div>
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('editing', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveItem" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($runEdit)
        <x-accounts.modal :title="'Edit ' . $label . ' payroll'" close="$set('runEdit', null)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bonus (% of salary, সবার)</label><input type="number" min="0" step="0.5" wire:model="runEdit.percent" class="{{ $fld }}">@error('runEdit.percent')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Due date</label><input type="text" readonly wire:model="runEdit.due_on" class="flatpickr-only-date {{ $fld }} cursor-pointer bg-white">@error('runEdit.due_on')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Bonus note</label><input type="text" wire:model="runEdit.note" placeholder="যেমন: Eid bonus" class="{{ $fld }}"></div>
            </div>
            <div class="text-[12.5px] text-[#6b7280]">সবার bonus = salary × %। কারো আলাদা bonus দিতে চাইলে তার row-এর Edit ব্যবহার করুন।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('runEdit', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveRun" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
