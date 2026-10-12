{{-- Lenders — design: Lenders.dc.html --}}
@php
    $s = $this->summary;
    $btn = 'text-[13px] px-[14px] py-[9px] border border-[#e5e7eb] rounded-[8px] bg-white text-[#374151]';
    $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
    $lbl = 'text-[13px] font-semibold';
    $seg = fn ($on) => $on
        ? 'rounded-[7px] border-0 bg-white px-[14px] py-[7px] text-[13px] font-bold text-[#312e81] shadow-[0_1px_2px_rgba(0,0,0,0.08)]'
        : 'rounded-[7px] border-0 bg-transparent px-[14px] py-[7px] text-[13px] text-[#4b5563]';
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1400px] flex-col gap-[18px]">
    <x-accounts.profile-tabs active="lenders" />

    <x-accounts.page-head title="Lenders" subtitle="কার কাছ থেকে ঋণ নিয়েছি, কত বাকি, পরের কিস্তি কবে">
        <button type="button" class="{{ $btn }}" wire:click="openVoucher('kisti')">কিস্তি দিন · Pay kisti</button>
        <button type="button" class="{{ $btn }}" wire:click="openLoanForm">+ New loan</button>
        <a href="{{ route('accounts.profiles.create', ['role' => 'lender']) }}" wire:navigate class="rounded-[8px] bg-[#4f46e5] px-[16px] py-[9px] text-[13px] font-semibold !text-white">+ New lender</a>
    </x-accounts.page-head>

    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
        <x-accounts.stat label="ঋণ নেওয়া · Borrowed" :value="$money($s['borrowed'])" :sub="$s['lenders'] . ' lenders · ' . $s['loans'] . ' loans'" />
        <x-accounts.stat label="মোট বাকি · Outstanding" :value="$money($s['outstanding'])" color="#b91c1c" sub="Including bank interest" />
        <x-accounts.stat label="এই মাসের কিস্তি" :value="$money($s['month'])"
                         :sub="$money($s['monthPaid']) . ' paid · ' . $money($s['monthLeft']) . ($s['monthNext'] ? ' on ' . $s['monthNext'] : ' left')" />
        <x-accounts.stat label="সাপোর্ট অগ্রিম · Support" :value="$money($s['support'])" color="#047857"
                         :sub="$s['supportWho'] ? $s['supportWho'] . ' · next kisti কমবে' : 'কোনো support নেই'" />
    </div>

    <div class="grid items-start gap-[16px] [grid-template-columns:repeat(auto-fit,minmax(320px,1fr))]">
        @forelse ($this->loans as $loan)
            @php $l = $this->card($loan); @endphp
            <section wire:key="loan-{{ $loan->id }}" class="flex flex-col gap-[14px] rounded-[14px] border border-[#e5e7eb] bg-white p-[18px]">
                <div class="flex items-start justify-between gap-[10px]">
                    <div>
                        <div class="text-[16px] font-bold">{{ $l['name'] }}</div>
                        <div class="mt-[2px] text-[12px] text-[#6b7280]">{{ $l['code'] }} · {{ $l['note'] }}</div>
                    </div>
                    <span class="whitespace-nowrap rounded-[6px] px-[8px] py-[3px] text-[11.5px] font-semibold {{ $l['typeClass'] }}">{{ $l['type'] }}</span>
                </div>
                <div class="flex flex-wrap gap-[6px]">
                    <span class="rounded-[6px] bg-[#eef2ff] px-[8px] py-[3px] text-[12px] font-semibold text-[#3730a3]">{{ $l['rule'] }}</span>
                    <span class="rounded-[6px] bg-[#f3f4f6] px-[8px] py-[3px] text-[12px] text-[#374151]">{{ $l['rate'] }}</span>
                </div>
                <div>
                    <div class="mb-[6px] flex justify-between text-[12.5px] text-[#4b5563]"><span>Paid {{ $l['paidCount'] }}</span><span class="font-semibold text-[#111827]">{{ $l['pct'] }}%</span></div>
                    <div class="h-[8px] overflow-hidden rounded-[5px] bg-[#eef0f3]"><div class="h-full rounded-[5px] bg-[#4f46e5]" style="width: {{ min($l['pct'], 100) }}%"></div></div>
                </div>
                <div class="grid grid-cols-2 gap-x-[14px] gap-y-[10px] text-[13px]">
                    <div><div class="text-[11.5px] text-[#6b7280]">নেওয়া</div><div class="font-semibold">{{ $l['borrowed'] }}</div></div>
                    <div><div class="text-[11.5px] text-[#6b7280]">মোট ফেরত দিতে হবে</div><div class="font-semibold">{{ $l['total'] }}</div></div>
                    <div><div class="text-[11.5px] text-[#6b7280]">দেওয়া হয়েছে</div><div class="font-semibold text-[#047857]">{{ $l['paid'] }}</div></div>
                    <div><div class="text-[11.5px] text-[#6b7280]">বাকি</div><div class="font-bold text-[#b91c1c]">{{ $l['outstanding'] }}</div></div>
                </div>
                <div class="flex items-center justify-between gap-[10px] rounded-[10px] border border-[#eef0f3] bg-[#f9fafb] px-[12px] py-[10px]">
                    <div><div class="text-[11.5px] text-[#6b7280]">পরের কিস্তি</div><div class="font-semibold">{{ $l['nextDate'] }}</div></div>
                    <div class="text-right"><div class="font-bold">{{ $l['nextAmt'] }}</div><div class="text-[11.5px] text-[#047857]">{{ $l['nextNote'] }}</div></div>
                </div>
                <div class="flex gap-[8px]">
                    <a href="#" wire:click.prevent="openVoucher('kisti', {{ $l['profileId'] ?? 'null' }}, {{ $loan->id }})" class="flex-1 rounded-[8px] bg-[#4f46e5] px-[12px] py-[9px] text-center text-[13px] font-semibold !text-white">Pay kisti</a>
                    <a href="#" wire:click.prevent="showSchedule({{ $loan->id }})" class="flex-1 rounded-[8px] border border-[#e5e7eb] px-[12px] py-[9px] text-center text-[13px] !text-[#374151]">Schedule</a>
                    @if ($l['profileId'])
                        <a href="{{ route('accounts.profiles.show', ['profile' => $l['profileId'], 'tab' => 'ledger', 'role' => 'lender']) }}" wire:navigate class="flex-1 rounded-[8px] border border-[#e5e7eb] px-[12px] py-[9px] text-center text-[13px] !text-[#374151]">Ledger</a>
                    @endif
                </div>
            </section>
        @empty
            <div class="rounded-[14px] border border-dashed border-[#c7d2fe] bg-white px-[18px] py-[36px] text-center text-[#6b7280]">
                কোনো চলতি ঋণ নেই। <button type="button" wire:click="openLoanForm" class="font-semibold text-[#4338ca]">+ New loan</button>
            </div>
        @endforelse
    </div>

    <section class="overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">
        <div class="flex items-center justify-between border-b border-[#f0f1f4] px-[18px] py-[14px]">
            <h2 class="m-0 text-[15px] font-bold">আগামী কিস্তি · Next 60 days</h2>
            <a href="#" wire:click.prevent="showSchedule(0)" class="text-[13px]">Full schedule</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[720px] text-[13px]">
                <thead>
                    <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                        <th class="px-[18px] py-[10px] font-semibold">Date</th>
                        <th class="px-[8px] py-[10px] font-semibold">Lender</th>
                        <th class="px-[8px] py-[10px] font-semibold">Kisti</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">EMI</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">Support adjust</th>
                        <th class="px-[8px] py-[10px] text-right font-semibold">দিতে হবে</th>
                        <th class="px-[18px] py-[10px] font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->upcoming as $r)
                        <tr class="border-t border-[#f0f1f4]">
                            <td class="whitespace-nowrap px-[18px] py-[11px] font-semibold">{{ $r['date'] }}</td>
                            <td class="px-[8px] py-[11px]">{{ $r['lender'] }}</td>
                            <td class="px-[8px] py-[11px] text-[#374151]">{{ $r['no'] }}</td>
                            <td class="px-[8px] py-[11px] text-right">{{ $r['emi'] }}</td>
                            <td class="px-[8px] py-[11px] text-right font-semibold text-[#047857]">{{ $r['support'] }}</td>
                            <td class="px-[8px] py-[11px] text-right font-bold">{{ $r['pay'] }}</td>
                            <td class="px-[18px] py-[11px]"><span class="rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $r['badge'] }}">{{ $r['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr class="border-t border-[#f0f1f4]"><td colspan="7" class="px-[18px] py-[24px] text-center text-[#6b7280]">আগামী ৬০ দিনে কোনো কিস্তি নেই।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Full schedule --}}
    @if ($scheduleLoan !== null)
        <x-accounts.modal title="Kisti schedule" close="closeSchedule" width="max-w-[820px]">
            <div class="-mx-[18px] -my-[16px] max-h-[70vh] overflow-auto">
                <table class="min-w-[680px] text-[13px]">
                    <thead class="sticky top-0 bg-[#f9fafb]">
                        <tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]">
                            <th class="px-[18px] py-[10px] font-semibold">Date</th><th class="px-[8px] py-[10px] font-semibold">Loan</th><th class="px-[8px] py-[10px] font-semibold">Kisti</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">EMI</th><th class="px-[8px] py-[10px] text-right font-semibold">Support</th>
                            <th class="px-[8px] py-[10px] text-right font-semibold">দিতে হবে</th><th class="px-[18px] py-[10px] font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->scheduleRows as $r)
                            <tr class="border-t border-[#f0f1f4]">
                                <td class="whitespace-nowrap px-[18px] py-[9px] font-semibold">{{ $r['date'] }} {{ $r['year'] }}</td>
                                <td class="px-[8px] py-[9px]">{{ $r['lender'] }}</td>
                                <td class="px-[8px] py-[9px] text-[#374151]">{{ $r['no'] }}</td>
                                <td class="px-[8px] py-[9px] text-right">{{ $r['emi'] }}</td>
                                <td class="px-[8px] py-[9px] text-right font-semibold text-[#047857]">{{ $r['support'] }}</td>
                                <td class="px-[8px] py-[9px] text-right font-bold">{{ $r['pay'] }}</td>
                                <td class="px-[18px] py-[9px]"><span class="rounded-[6px] px-[8px] py-[3px] text-[12px] font-semibold {{ $r['badge'] }}">{{ $r['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-[18px] py-[24px] text-center text-[#6b7280]">এই ঋণের নির্দিষ্ট কিস্তি নেই (when possible)।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-accounts.modal>
    @endif

    {{-- New loan / EMI rule (spec §9) --}}
    @if ($showLoanForm)
        @php $preview = $this->preview; @endphp
        <x-accounts.modal title="নতুন ঋণ · New loan / EMI rule" close="closeLoanForm" width="max-w-[760px]">
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                <div class="flex flex-col gap-[6px]">
                    <label for="ln-lender" class="{{ $lbl }}">Lender <span class="text-[#b91c1c]">*</span></label>
                    <select id="ln-lender" wire:model="loanForm.profile_id" class="{{ $input }}">
                        <option value="">Select lender</option>
                        @foreach ($this->lenderOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('accounts.profiles.create', ['role' => 'lender']) }}" wire:navigate class="text-[12px]">+ New lender profile</a>
                    @error('loanForm.profile_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="ln-name" class="{{ $lbl }}">Loan name</label>
                    <input id="ln-name" type="text" wire:model="loanForm.name" placeholder="City Bank SME Loan" class="{{ $input }}">
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="ln-principal" class="{{ $lbl }}">Principal (৳) <span class="text-[#b91c1c]">*</span></label>
                    <input id="ln-principal" type="number" min="0" step="0.01" wire:model.live.debounce.400ms="loanForm.principal" class="{{ $input }}">
                    @error('loanForm.principal') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-[6px]">
                    <label for="ln-acc" class="{{ $lbl }}">Received in</label>
                    <select id="ln-acc" wire:model="loanForm.cash_account_id" class="{{ $input }}">
                        @foreach ($this->cashAccounts as $a)
                            <option value="{{ $a['id'] }}">{{ $a['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-[6px]">
                <span class="{{ $lbl }}">Interest / profit</span>
                <div class="flex flex-wrap items-end gap-[10px]">
                    <div role="radiogroup" class="flex gap-[4px] rounded-[10px] bg-[#f3f4f6] p-[4px]">
                        @foreach (['none' => 'No interest', 'flat' => 'Flat', 'reducing' => 'Reducing'] as $k => $label)
                            <button type="button" wire:click="$set('loanForm.rate_type', '{{ $k }}')" class="{{ $seg($loanForm['rate_type'] === $k) }}">{{ $label }}</button>
                        @endforeach
                    </div>
                    @if ($loanForm['rate_type'] !== 'none')
                        <div class="flex w-[130px] flex-col gap-[6px]">
                            <label for="ln-rate" class="text-[12px] text-[#4b5563]">Rate % / year</label>
                            <input id="ln-rate" type="number" min="0" step="0.01" wire:model.live.debounce.400ms="loanForm.rate" class="{{ $input }}">
                        </div>
                    @endif
                    @if ($loanForm['rate_type'] === 'flat')
                        <div class="flex w-[180px] flex-col gap-[6px]">
                            <label for="ln-total" class="text-[12px] text-[#4b5563]">অথবা মোট ফেরত (৳)</label>
                            <input id="ln-total" type="number" min="0" step="0.01" wire:model.live.debounce.400ms="loanForm.total_payable" class="{{ $input }}">
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-[6px]">
                <span class="{{ $lbl }}">কিস্তির নিয়ম · Frequency</span>
                <div class="flex flex-wrap gap-[6px]">
                    @foreach (\App\Services\Profiles\InstallmentScheduler::FREQUENCIES as $f)
                        <x-accounts.chip :on="$loanForm['frequency'] === $f" wire:click="$set('loanForm.frequency', '{{ $f }}')">{{ ucfirst($f) }}</x-accounts.chip>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-3">
                @if ($loanForm['frequency'] === 'custom')
                    <div class="flex flex-col gap-[6px] sm:col-span-2">
                        <label for="ln-offsets" class="{{ $lbl }}">Custom days (start থেকে)</label>
                        <input id="ln-offsets" type="text" wire:model.live.debounce.400ms="loanForm.custom_offsets" placeholder="15, 45, 90" class="{{ $input }}">
                        @error('loanForm.custom_offsets') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="flex flex-col gap-[6px]">
                        <label for="ln-n" class="{{ $lbl }}">Number of kisti</label>
                        <input id="ln-n" type="number" min="1" wire:model.live.debounce.400ms="loanForm.installments" class="{{ $input }}">
                        @error('loanForm.installments') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
                    </div>
                @endif
                <div class="flex flex-col gap-[6px]">
                    <label for="ln-start" class="{{ $lbl }}">Start date</label>
                    <input id="ln-start" type="date" wire:model.live="loanForm.start_date" class="{{ $input }}">
                </div>
                <div class="flex flex-col gap-[6px] {{ $loanForm['frequency'] === 'custom' ? 'sm:col-span-3' : '' }}">
                    <label for="ln-support" class="{{ $lbl }}">বেশি দিলে (support)</label>
                    <select id="ln-support" wire:model="loanForm.support_rule" class="{{ $input }}">
                        <option value="next_kisti">পরের কিস্তি থেকে কমবে</option>
                        <option value="principal">Principal থেকে কমবে</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-[6px]">
                <label for="ln-note" class="{{ $lbl }}">Note</label>
                <input id="ln-note" type="text" wire:model="loanForm.note" placeholder="Gulshan branch · A/C ••7781" class="{{ $input }}">
            </div>

            <div class="overflow-hidden rounded-[10px] border border-[#eef0f3]">
                <div class="flex justify-between bg-[#f9fafb] px-[12px] py-[8px] text-[12.5px] font-semibold text-[#374151]">
                    <span>Schedule preview</span>
                    @if ($preview)
                        <span>{{ count($preview) }} kisti · মোট {{ $money(array_sum(array_column($preview, 'amount'))) }}</span>
                    @endif
                </div>
                <div class="max-h-[220px] overflow-auto">
                    <table class="text-[12.5px]">
                        <tbody>
                            @forelse ($preview as $r)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="px-[12px] py-[6px] text-[#6b7280]">{{ $r['no'] }}</td>
                                    <td class="px-[8px] py-[6px]">{{ $r['due_date']->format('d M Y') }}</td>
                                    <td class="px-[8px] py-[6px] text-right text-[#6b7280]">Principal {{ $money($r['principal']) }}</td>
                                    <td class="px-[8px] py-[6px] text-right text-[#6b7280]">Interest {{ $money($r['interest']) }}</td>
                                    <td class="px-[12px] py-[6px] text-right font-bold">{{ $money($r['amount']) }}</td>
                                </tr>
                            @empty
                                <tr><td class="px-[12px] py-[12px] text-[#6b7280]">Principal আর কিস্তির নিয়ম দিলে schedule এখানে দেখাবে।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeLoanForm" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
                <button type="button" wire:click="saveLoan" wire:loading.attr="disabled" wire:target="saveLoan" class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white">Save loan</button>
            </x-slot:footer>
        </x-accounts.modal>
    @endif

    @include('livewire.accounts.profiles.partials.voucher-modal')
</div>
