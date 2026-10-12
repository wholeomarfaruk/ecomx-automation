{{-- Payroll run — a salary sheet while it's a draft, the posted payroll after. --}}
@php
    $run = $this->run;
    $draft = $run?->isDraft();
    $fld = 'w-full rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[14px]';
    $cell = 'w-[92px] rounded-[7px] border border-[#d1d5db] bg-white px-[6px] py-[5px] text-right text-[12.5px] focus:border-[#4f46e5] focus:outline-none';
    $small = 'w-[64px] rounded-[7px] border border-[#d1d5db] bg-white px-[6px] py-[5px] text-right text-[12.5px] focus:border-[#4f46e5] focus:outline-none';
    $th = 'px-[6px] py-[8px] text-right font-semibold whitespace-nowrap';
    $entryNo = $run?->journal_entry_id ? \App\Models\JournalEntry::whereKey($run->journal_entry_id)->value('entry_number') : null;
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1600px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="employees" />
    <a href="{{ route('accounts.profiles.employees') }}" wire:navigate class="flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        Employees</a>

    <x-accounts.page-head :title="$label . ' payroll'" :subtitle="! $run ? 'এই মাসের payroll এখনো তৈরি হয়নি।' : ($draft ? 'Draft salary sheet — post না করা পর্যন্ত হিসাবে কিছু যায়নি · due ' . $run->due_on?->format('d M Y') : 'Posted as ' . ($entryNo ?? '—') . ($run->posted_at ? ' · ' . $run->posted_at->format('d M Y') : '') . ' · due ' . $run->due_on?->format('d M Y'))">
        @if ($run && ! $draft)
            <a href="{{ route('accounts.profiles.payslips', ['month' => $month]) }}" target="_blank" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] !text-[#374151]">Payslips</a>
            <button type="button" wire:click="editRun" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Adjust bonus · due date</button>
            @if ((float) $run->paid <= 0.004 && \App\Support\AccountsAccess::can('approve'))
                <button type="button" wire:click="$set('showRevert', true)" class="rounded-[8px] border border-[#fecaca] bg-white px-[14px] py-[9px] text-[13px] text-[#b91c1c]">Revert to draft</button>
            @endif
        @endif
        <button type="button" onclick="window.print()" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Print</button>
    </x-accounts.page-head>

    @if (! $run)
        <div class="rounded-[14px] border border-dashed border-[#c7d2fe] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">
            {{ $label }} payroll তৈরি হয়নি। <a href="{{ route('accounts.profiles.employees') }}" wire:navigate class="font-semibold">Employees</a> থেকে "Prepare payroll" দিন।
        </div>

    {{-- ===================== DRAFT: salary sheet ===================== --}}
    @elseif ($draft)
        <div x-data="{
                sheet: $wire.entangle('sheet'),
                bulkField: 'bonus_pct', bulkValue: '',
                num(v) { v = parseFloat(v); return isNaN(v) || v < 0 ? 0 : Math.round(v * 100) / 100 },
                calc(r) {
                    if (! r) return { ot: 0, gross: 0, absent: 0, ded: 0, net: 0 };
                    const n = (k) => this.num(r[k]);
                    const fixed = n('salary') + n('house_rent') + n('medical') + n('conveyance') + n('other_allowance');
                    const ot = Math.round(n('ot_hours') * n('ot_rate') * 100) / 100;
                    const gross = fixed + ot + n('bonus') + n('incentive') + n('arrears');
                    const days = Math.max(1, parseFloat(r.working_days) || 30);
                    const absent = Math.round(Math.min(fixed, fixed / days * Math.min(days, n('absent_days'))) * 100) / 100;
                    const ded = absent + n('advance_deduction') + n('tax') + n('other_deduction');
                    return { ot, gross, absent, ded, net: Math.round((gross - ded) * 100) / 100 };
                },
                total(f) {
                    return Object.values(this.sheet || {}).filter((r) => r.include)
                        .reduce((a, r) => a + (['ot', 'gross', 'absent', 'ded', 'net'].includes(f) ? this.calc(r)[f] : this.num(r[f])), 0);
                },
                count() { return Object.values(this.sheet || {}).filter((r) => r.include).length },
                money(v) { return (v < 0 ? '−৳' : '৳') + Math.round(Math.abs(v)).toLocaleString('en-IN') },
                applyBulk() {
                    const v = this.num(this.bulkValue);
                    for (const r of Object.values(this.sheet || {})) {
                        if (! r.include) continue;
                        if (this.bulkField === 'bonus_pct') r.bonus = Math.round(this.num(r.salary) * v) / 100;
                        else if (this.bulkField === 'ot_default') r.ot_rate = Math.round(this.num(r.salary) / 208 * 2 * 100) / 100;
                        else r[this.bulkField] = v;
                    }
                },
             }" class="flex flex-col gap-[16px]">

            {{-- Summary --}}
            <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(170px,1fr))]">
                <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]"><div class="text-[12.5px] text-[#4b5563]">Employees</div><div class="mt-[6px] text-[24px] font-bold" x-text="count()"></div><div class="mt-[4px] text-[12px] text-[#6b7280]">included in this sheet</div></div>
                <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]"><div class="text-[12.5px] text-[#4b5563]">Gross earnings</div><div class="mt-[6px] text-[24px] font-bold" x-text="money(total('gross'))"></div><div class="mt-[4px] text-[12px] text-[#6b7280]" x-text="'Bonus ' + money(total('bonus')) + ' · OT ' + money(total('ot'))"></div></div>
                <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]"><div class="text-[12.5px] text-[#4b5563]">Deductions</div><div class="mt-[6px] text-[24px] font-bold text-[#4338ca]" x-text="money(total('ded'))"></div><div class="mt-[4px] text-[12px] text-[#6b7280]" x-text="'Advance ' + money(total('advance_deduction')) + ' · absent ' + money(total('absent'))"></div></div>
                <div class="rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[16px]"><div class="text-[12.5px] text-[#4b5563]">Net · দিতে হবে</div><div class="mt-[6px] text-[24px] font-bold text-[#b91c1c]" x-text="money(total('net'))"></div><div class="mt-[4px] text-[12px] text-[#6b7280]">Post হলে Salary Payable-এ উঠবে</div></div>
            </div>

            {{-- Tools --}}
            <section class="flex flex-wrap items-end gap-[12px] rounded-[14px] border border-[#e5e7eb] bg-white px-[18px] py-[14px]">
                <div class="flex flex-col gap-[4px]"><label class="text-[12px] font-semibold text-[#4b5563]">Due date</label><input type="text" readonly wire:model="head.due_on" class="flatpickr-only-date w-[140px] cursor-pointer rounded-[8px] border border-[#d1d5db] bg-white px-[10px] py-[7px] text-[13px]"></div>
                <div class="flex flex-col gap-[4px]"><label class="text-[12px] font-semibold text-[#4b5563]">Bonus note</label><input type="text" wire:model="head.bonus_note" placeholder="যেমন: Eid bonus" class="w-[160px] rounded-[8px] border border-[#d1d5db] px-[10px] py-[7px] text-[13px]"></div>
                <div class="flex flex-col gap-[4px]">
                    <label class="text-[12px] font-semibold text-[#4b5563]">সবার জন্য একসাথে</label>
                    <div class="flex gap-[6px]">
                        <select x-model="bulkField" class="rounded-[8px] border border-[#d1d5db] bg-white py-[7px] pl-[8px] pr-[26px] text-[13px]">
                            <option value="bonus_pct">Bonus = % of salary</option>
                            <option value="bonus">Bonus (৳)</option>
                            <option value="house_rent">House rent (৳)</option>
                            <option value="medical">Medical (৳)</option>
                            <option value="conveyance">Conveyance (৳)</option>
                            <option value="other_allowance">Other allowance (৳)</option>
                            <option value="working_days">Working days</option>
                            <option value="ot_default">OT rate = salary ÷ 208 × 2</option>
                            <option value="ot_rate">OT rate (৳ / hour)</option>
                            <option value="tax">Tax (৳)</option>
                        </select>
                        <input type="number" min="0" step="0.01" x-model="bulkValue" x-show="bulkField !== 'ot_default'" placeholder="0" class="w-[90px] rounded-[8px] border border-[#d1d5db] px-[8px] py-[7px] text-[13px]">
                        <button type="button" x-on:click="applyBulk()" class="rounded-[8px] border border-[#c7d2fe] bg-[#eef2ff] px-[12px] py-[7px] text-[13px] font-semibold text-[#3730a3]">Apply</button>
                    </div>
                </div>
                <button type="button" wire:click="copyLastMonth" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[7px] text-[13px] text-[#374151]">Copy allowances from last month</button>
                @if ($this->missing)
                    <select x-on:change="if ($event.target.value) { $wire.addEmployee(parseInt($event.target.value)); $event.target.value = '' }" class="rounded-[8px] border border-[#d1d5db] bg-white py-[7px] pl-[8px] pr-[26px] text-[13px]">
                        <option value="">+ Add employee</option>
                        @foreach ($this->missing as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach
                    </select>
                @endif
                <div class="ml-auto flex gap-[8px]">
                    <button type="button" wire:click="saveSheet" wire:loading.attr="disabled" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[16px] py-[9px] text-[13px] font-semibold text-[#374151]">Save draft</button>
                    @if (\App\Support\AccountsAccess::can('post'))
                        <button type="button" wire:click="postPayroll" wire:confirm="Salary sheet post করবেন? Journal হবে আর advance কাটা হবে — পরে বদলাতে হলে 'Revert to draft' লাগবে।" wire:loading.attr="disabled" class="rounded-[8px] border-0 bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold text-white">Post payroll</button>
                    @endif
                </div>
            </section>

            {{-- Sheet --}}
            <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-[1900px] text-[12.5px]">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-[0.04em] text-[#6b7280]">
                                <th class="sticky left-0 z-10 bg-white"></th>
                                <th colspan="9" class="border-b border-l border-[#e0e7ff] bg-[#eef2ff] py-[6px] text-center font-semibold text-[#3730a3]">Earnings · আয়</th>
                                <th colspan="3" class="border-b border-l border-[#fde68a] bg-[#fffbeb] py-[6px] text-center font-semibold text-[#92400e]">Attendance · উপস্থিতি</th>
                                <th colspan="4" class="border-b border-l border-[#fecaca] bg-[#fef2f2] py-[6px] text-center font-semibold text-[#991b1b]">Deductions · কাটা</th>
                                <th colspan="3" class="border-b border-l border-[#d1fae5] bg-[#ecfdf5] py-[6px] text-center font-semibold text-[#065f46]">Total</th>
                            </tr>
                            <tr class="text-left text-[11px] uppercase tracking-[0.03em] text-[#6b7280]">
                                <th class="sticky left-0 z-10 min-w-[200px] bg-white px-[14px] py-[8px] font-semibold">Employee</th>
                                <th class="{{ $th }} border-l border-[#eef0f3]">Salary</th>
                                <th class="{{ $th }}">House rent</th>
                                <th class="{{ $th }}">Medical</th>
                                <th class="{{ $th }}">Conveyance</th>
                                <th class="{{ $th }}">Other allow.</th>
                                <th class="{{ $th }}">OT hrs × rate</th>
                                <th class="{{ $th }}">Bonus</th>
                                <th class="{{ $th }}">Incentive</th>
                                <th class="{{ $th }}">Arrears</th>
                                <th class="{{ $th }} border-l border-[#eef0f3]">Work days</th>
                                <th class="{{ $th }}">Absent</th>
                                <th class="{{ $th }}">Absent ৳</th>
                                <th class="{{ $th }} border-l border-[#eef0f3]">Advance</th>
                                <th class="{{ $th }}">Tax</th>
                                <th class="{{ $th }}">Fine / other</th>
                                <th class="{{ $th }}">Total ded.</th>
                                <th class="{{ $th }} border-l border-[#eef0f3]">Gross</th>
                                <th class="{{ $th }}">Net</th>
                                <th class="px-[10px] py-[8px] font-semibold">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($run->items as $item)
                                @php
                                    $id = $item->id;
                                    $advBal = round((float) $item->profile?->advances->sum('balance'), 2);
                                    $e = fn ($f) => $errors->first("sheet.{$id}.{$f}");
                                @endphp
                                <tr wire:key="sh-{{ $id }}" class="border-t border-[#f0f1f4] align-top" :class="sheet[{{ $id }}]?.include ? '' : 'opacity-50'">
                                    <td class="sticky left-0 z-10 bg-white px-[14px] py-[8px]">
                                        <label class="flex items-start gap-[8px]">
                                            <input type="checkbox" x-model="sheet[{{ $id }}].include" class="mt-[3px] accent-[#4f46e5]" title="Include in this payroll">
                                            <span><span class="block font-semibold text-[#111827]">{{ $item->profile?->display_name }}</span>
                                                <span class="block text-[11px] text-[#6b7280]">{{ $item->profile?->roleSettings('employee')['designation'] ?? '' }}{{ $advBal > 0 ? ' · advance ' . \App\Support\Bdt::money($advBal) : '' }}</span></span>
                                        </label>
                                    </td>
                                    @foreach (['salary', 'house_rent', 'medical', 'conveyance', 'other_allowance'] as $f)
                                        <td class="px-[6px] py-[8px] text-right {{ $f === 'salary' ? 'border-l border-[#eef0f3]' : '' }}"><input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].{{ $f }}" class="{{ $cell }}"></td>
                                    @endforeach
                                    <td class="whitespace-nowrap px-[6px] py-[8px] text-right">
                                        <input type="number" min="0" step="0.5" x-model="sheet[{{ $id }}].ot_hours" class="{{ $small }}" title="Hours"> ×
                                        <input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].ot_rate" class="{{ $small }}" title="Rate per hour">
                                        <div class="mt-[2px] text-[11px] text-[#6b7280]" x-text="calc(sheet[{{ $id }}]).ot > 0 ? money(calc(sheet[{{ $id }}]).ot) : ''"></div>
                                    </td>
                                    @foreach (['bonus', 'incentive', 'arrears'] as $f)
                                        <td class="px-[6px] py-[8px] text-right"><input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].{{ $f }}" class="{{ $cell }}"></td>
                                    @endforeach
                                    <td class="border-l border-[#eef0f3] px-[6px] py-[8px] text-right"><input type="number" min="1" max="31" step="0.5" x-model="sheet[{{ $id }}].working_days" class="{{ $small }}"></td>
                                    <td class="px-[6px] py-[8px] text-right"><input type="number" min="0" step="0.5" x-model="sheet[{{ $id }}].absent_days" class="{{ $small }}"></td>
                                    <td class="whitespace-nowrap px-[6px] py-[8px] pt-[13px] text-right text-[#b45309]" x-text="calc(sheet[{{ $id }}]).absent > 0 ? money(calc(sheet[{{ $id }}]).absent) : '—'"></td>
                                    <td class="border-l border-[#eef0f3] px-[6px] py-[8px] text-right">
                                        <input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].advance_deduction" @if ($advBal <= 0) disabled @endif class="{{ $cell }} {{ $advBal <= 0 ? 'bg-[#f9fafb] text-[#9ca3af]' : '' }}" title="Max {{ \App\Support\Bdt::money($advBal) }}">
                                        @if ($e('advance_deduction'))<div class="mt-[2px] max-w-[120px] text-[11px] text-[#b91c1c]">{{ $e('advance_deduction') }}</div>@endif
                                    </td>
                                    <td class="px-[6px] py-[8px] text-right"><input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].tax" class="{{ $cell }}"></td>
                                    <td class="px-[6px] py-[8px] text-right"><input type="number" min="0" step="0.01" x-model="sheet[{{ $id }}].other_deduction" class="{{ $cell }}"></td>
                                    <td class="whitespace-nowrap px-[6px] py-[8px] pt-[13px] text-right text-[#4338ca]" x-text="money(calc(sheet[{{ $id }}]).ded)"></td>
                                    <td class="whitespace-nowrap border-l border-[#eef0f3] px-[6px] py-[8px] pt-[13px] text-right font-semibold" x-text="money(calc(sheet[{{ $id }}]).gross)"></td>
                                    <td class="whitespace-nowrap px-[6px] py-[8px] pt-[13px] text-right font-bold" :class="calc(sheet[{{ $id }}]).net < 0 ? 'text-[#b91c1c]' : 'text-[#065f46]'" x-text="money(calc(sheet[{{ $id }}]).net)"></td>
                                    <td class="px-[10px] py-[8px]">
                                        <input type="text" x-model="sheet[{{ $id }}].note" placeholder="যেমন: 2 দিন ছুটি" class="w-[170px] rounded-[7px] border border-[#d1d5db] px-[8px] py-[5px] text-[12.5px]">
                                        @if ($e('net'))<div class="mt-[2px] text-[11px] text-[#b91c1c]">{{ $e('net') }}</div>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-[#e5e7eb] bg-[#f9fafb] font-semibold">
                                <td class="sticky left-0 z-10 bg-[#f9fafb] px-[14px] py-[10px]" x-text="'Total · ' + count() + ' employees'"></td>
                                @foreach (['salary', 'house_rent', 'medical', 'conveyance', 'other_allowance', 'ot', 'bonus', 'incentive', 'arrears'] as $f)
                                    <td class="whitespace-nowrap px-[6px] py-[10px] text-right" x-text="money(total('{{ $f }}'))"></td>
                                @endforeach
                                <td></td><td></td>
                                <td class="whitespace-nowrap px-[6px] py-[10px] text-right text-[#b45309]" x-text="money(total('absent'))"></td>
                                @foreach (['advance_deduction', 'tax', 'other_deduction'] as $f)
                                    <td class="whitespace-nowrap px-[6px] py-[10px] text-right" x-text="money(total('{{ $f }}'))"></td>
                                @endforeach
                                <td class="whitespace-nowrap px-[6px] py-[10px] text-right text-[#4338ca]" x-text="money(total('ded'))"></td>
                                <td class="whitespace-nowrap px-[6px] py-[10px] text-right" x-text="money(total('gross'))"></td>
                                <td class="whitespace-nowrap px-[6px] py-[10px] text-right text-[#065f46]" x-text="money(total('net'))"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="border-t border-[#f0f1f4] px-[18px] py-[10px] text-[12px] text-[#6b7280]">
                    Gross = salary + allowances + OT + bonus + incentive + arrears · Absent ৳ = (salary + allowances) ÷ work days × absent days · Net = gross − সব কাটা।
                    টিক তুলে দিলে ঐ কর্মী এই মাসের payroll-এ থাকবে না (hold)। Post হলে: Dr Salary expense / Cr Salary payable (net) · Salary advance · TDS payable · Other income (fine)।
                </div>
            </section>

            <div class="flex flex-col gap-[6px]">
                <label class="text-[12px] font-semibold text-[#4b5563]">Sheet note</label>
                <textarea wire:model="head.note" rows="2" placeholder="যেমন: Eid-এর জন্য এই মাসে 2 দিন বেশি ছুটি" class="max-w-[720px] rounded-[9px] border border-[#d1d5db] px-[12px] py-[9px] text-[13px]"></textarea>
            </div>
        </div>

    {{-- ===================== POSTED ===================== --}}
    @else
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
            <x-accounts.stat label="Gross earnings" :value="$money((float) $run->gross + (float) $run->bonus)" :sub="$run->items->where('include', true)->count() . ' employees · bonus ' . $money($run->bonus)" />
            <x-accounts.stat label="Deductions" :value="$money((float) $run->advance_deducted + (float) $run->other_deducted)" color="#4338ca" :sub="'Advance ' . $money($run->advance_deducted) . ' · other ' . $money($run->other_deducted)" />
            <x-accounts.stat label="Net · দিতে হবে" :value="$money($run->net)" :sub="$money($run->paid) . ' paid · ' . $money($run->unpaid()) . ' বাকি'" :color="$run->unpaid() > 0.5 ? '#b91c1c' : '#047857'" />
            <x-accounts.stat label="Posted" :value="$entryNo ?? '—'" :sub="($run->postedBy?->name ?? '') . ($run->posted_at ? ' · ' . $run->posted_at->format('d M, g:i A') : '')" />
        </div>

        <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] text-[13px]">
                    <thead>
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="px-[18px] py-[10px] font-semibold">Employee</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Salary</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Allowances</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">OT · bonus · other</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Gross</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Deductions</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Net</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">Paid</th>
                            <th class="px-[8px] py-[10px] font-semibold">Status</th>
                            <th class="acc-no-print px-[18px] py-[10px] text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($run->items as $item)
                            @php
                                $allow = (float) $item->house_rent + (float) $item->medical + (float) $item->conveyance + (float) $item->other_allowance;
                                $extra = (float) $item->ot_amount + (float) $item->bonus + (float) $item->incentive + (float) $item->arrears;
                            @endphp
                            <tr wire:key="pi-{{ $item->id }}" @class(['border-t border-[#f0f1f4]', 'opacity-50' => ! $item->include])>
                                <td class="px-[18px] py-[11px]"><div class="font-semibold">{{ $item->profile?->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $item->profile?->roleSettings('employee')['designation'] ?? '' }}{{ $item->note ? ' · ' . $item->note : '' }}</div></td>
                                <td class="px-[8px] py-[11px] text-right">{{ $money($item->salary) }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ $allow > 0 ? $money($allow) : '—' }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ $extra > 0 ? $money($extra) : '—' }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ $money($item->gross) }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ (float) $item->total_deduction > 0 ? $money($item->total_deduction) : '—' }}</td>
                                <td class="px-[8px] py-[11px] text-right font-semibold">{{ $money($item->net) }}</td>
                                <td class="px-[8px] py-[11px] text-right">{{ $money($item->paid_amount) }}</td>
                                <td class="px-[8px] py-[11px]">
                                    @if (! $item->include)
                                        <span class="rounded-[6px] bg-[#f3f4f6] px-[8px] py-[3px] text-[12px] font-semibold text-[#4b5563]">On hold</span>
                                    @elseif ($item->unpaid() > 0.5)
                                        <span class="rounded-[6px] bg-[#fef3c7] px-[8px] py-[3px] text-[12px] font-semibold text-[#92400e]">{{ $money($item->unpaid()) }} বাকি</span>
                                    @else
                                        <span class="rounded-[6px] bg-[#d1fae5] px-[8px] py-[3px] text-[12px] font-semibold text-[#065f46]">Paid</span>
                                    @endif
                                </td>
                                <td class="acc-no-print whitespace-nowrap px-[18px] py-[11px] text-right">
                                    @if ($item->include)
                                        <a href="{{ route('accounts.profiles.payslips', ['month' => $month, 'item' => $item->id]) }}" target="_blank">Payslip</a>
                                        <span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="editItem({{ $item->id }})">Adjust</a>
                                        @if ($item->unpaid() > 0.5)
                                            <span class="text-[#d1d5db]"> · </span><a href="#" wire:click.prevent="openVoucher('salary', {{ $item->master_profile_id }})" class="font-semibold">Pay</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ((float) $run->paid > 0.004)
                <div class="border-t border-[#f0f1f4] px-[18px] py-[10px] text-[12px] text-[#6b7280]">কিছু বেতন দেওয়া হয়ে গেছে, তাই draft-এ ফেরানো যাবে না — বদলাতে "Adjust" দিন (পার্থক্য adjustment journal হিসেবে post হবে)।</div>
            @endif
        </section>
    @endif

    {{-- Adjust one employee (posted) --}}
    @if ($editing)
        <x-accounts.modal :title="'Adjust payroll · ' . $editing['name']" close="$set('editing', null)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Salary (৳)</label><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="editing.salary" class="{{ $fld }}">@error('editing.salary')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bonus (৳)</label><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="editing.bonus" class="{{ $fld }}">@error('editing.bonus')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">কারণ (optional)</label><input type="text" wire:model="editing.note" placeholder="যেমন: Puja bonus, overtime" class="{{ $fld }}"></div>
            </div>
            <div class="rounded-[10px] bg-[#f9fafb] px-[12px] py-[10px] text-[13px] text-[#374151]">
                Paid {{ $money($editing['paid']) }} · পার্থক্যটা একটা adjustment journal হিসেবে post হবে (Salary expense ↔ Salary payable)।
            </div>
            <x-slot:footer>
                <button type="button" wire:click="$set('editing', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveItem" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($runEdit)
        <x-accounts.modal :title="'Adjust ' . $label . ' payroll'" close="$set('runEdit', null)">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Bonus (% of salary, সবার)</label><input type="number" min="0" step="0.5" wire:model="runEdit.percent" class="{{ $fld }}">@error('runEdit.percent')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px]"><label class="text-[13px] font-semibold">Due date</label><input type="text" readonly wire:model="runEdit.due_on" class="flatpickr-only-date {{ $fld }} cursor-pointer bg-white">@error('runEdit.due_on')<span class="text-[12px] text-[#b91c1c]">{{ $message }}</span>@enderror</div>
                <div class="flex flex-col gap-[6px] sm:col-span-2"><label class="text-[13px] font-semibold">Bonus note</label><input type="text" wire:model="runEdit.note" placeholder="যেমন: Eid bonus" class="{{ $fld }}"></div>
            </div>
            <div class="text-[12.5px] text-[#6b7280]">Post হয়ে যাওয়া payroll — প্রতিটা পরিবর্তন adjustment journal হিসেবে post হবে। কিছু না দেওয়া থাকলে "Revert to draft" দিয়ে sheet-এ বদলানোই ভালো।</div>
            <x-slot:footer>
                <button type="button" wire:click="$set('runEdit', null)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveRun" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @if ($showRevert)
        <x-accounts.modal title="Revert to draft" close="$set('showRevert', false)">
            <div class="text-[13.5px] text-[#374151]">Payroll journal-এর উল্টো entry post হবে, advance থেকে যা কাটা হয়েছিল তা ফেরত যাবে, আর sheet আবার edit করা যাবে।</div>
            <input type="text" wire:model="revertReason" placeholder="কারণ (optional)" class="{{ $fld }}">
            <x-slot:footer>
                <button type="button" wire:click="$set('showRevert', false)" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="revertToDraft" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#b91c1c] px-[20px] py-[10px] text-[14px] font-bold text-white">Revert to draft</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
