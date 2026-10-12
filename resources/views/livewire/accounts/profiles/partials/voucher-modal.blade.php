{{-- Shared voucher modal (HandlesVoucherModals). --}}
@if ($voucherType)
    @php
        $input = 'w-full text-[14px] px-[12px] py-[10px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
        $lbl = 'text-[13px] font-semibold';
        $picked = $this->voucherProfile;
        $kisti = $this->voucherKisti;
    @endphp
    <x-accounts.modal :title="$this::VOUCHER_TITLES[$voucherType]" close="closeVoucher">
        <div class="flex flex-col gap-[6px]">
            <span class="{{ $lbl }}">{{ ucfirst($this::VOUCHER_ROLES[$voucherType]) }} <span class="text-[#b91c1c]">*</span></span>
            @if ($picked)
                <div class="flex items-center justify-between gap-3 rounded-[9px] border border-[#c7d2fe] bg-[#eef2ff] px-[12px] py-[9px]">
                    <div class="min-w-0">
                        <div class="truncate font-semibold">{{ $picked->display_name }}</div>
                        <div class="text-[11.5px] text-[#6b7280]">{{ $picked->code }} · {{ $picked->phone ?: '—' }}</div>
                    </div>
                    <button type="button" wire:click="$set('voucher.profile_id', null)" class="text-[12.5px] font-semibold text-[#4338ca]">Change</button>
                </div>
            @else
                <input type="search" wire:model.live.debounce.300ms="voucherSearch" placeholder="নাম, phone বা code দিয়ে খুঁজুন" class="{{ $input }}" autofocus>
                <div class="flex flex-col overflow-hidden rounded-[9px] border border-[#eef0f3]">
                    @forelse ($this->voucherMatches as $m)
                        <button type="button" wire:key="vm-{{ $m['id'] }}" wire:click="pickVoucherProfile({{ $m['id'] }})"
                                class="flex justify-between gap-3 border-t border-[#f0f1f4] px-[12px] py-[8px] text-left first:border-t-0 hover:bg-[#f9fafb]">
                            <span class="font-semibold">{{ $m['name'] }}</span>
                            <span class="text-[12px] text-[#6b7280]">{{ $m['meta'] }}</span>
                        </button>
                    @empty
                        <div class="px-[12px] py-[10px] text-[13px] text-[#6b7280]">কোনো profile পাওয়া যায়নি।</div>
                    @endforelse
                </div>
            @endif
            @error('voucher.profile_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
        </div>

        @if ($voucherType === 'kisti' && $picked)
            <div class="flex flex-col gap-[6px]">
                <label for="v-loan" class="{{ $lbl }}">Loan</label>
                <select id="v-loan" wire:model.live="voucher.loan_id" class="{{ $input }}">
                    <option value="">Select loan</option>
                    @foreach ($this->voucherLoans as $l)
                        <option value="{{ $l['id'] }}">{{ $l['name'] }}</option>
                    @endforeach
                </select>
                @error('voucher.loan_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
            <div class="flex flex-col gap-[6px]">
                <label for="v-amount" class="{{ $lbl }}">Amount (৳) <span class="text-[#b91c1c]">*</span></label>
                <input id="v-amount" type="number" step="0.01" min="0" wire:model="voucher.amount" class="{{ $input }}">
                @if ($kisti)
                    <span class="text-[12px] text-[#6b7280]">Kisti {{ $kisti['no'] }}{{ $kisti['of'] ? '/' . $kisti['of'] : '' }} · {{ $kisti['date'] }} · due {{ \App\Support\Bdt::money($kisti['due']) }}. বেশি দিলে support হিসেবে {{ $kisti['rule'] }}।</span>
                @endif
                @error('voucher.amount') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col gap-[6px]">
                <label for="v-date" class="{{ $lbl }}">Date</label>
                <input id="v-date" type="date" wire:model="voucher.date" class="{{ $input }}">
            </div>
            <div class="flex flex-col gap-[6px] {{ $voucherType === 'salary_advance' ? '' : 'sm:col-span-2' }}">
                <label for="v-account" class="{{ $lbl }}">{{ in_array($voucherType, ['collect', 'capital']) ? 'Received in' : 'Paid from' }}</label>
                <select id="v-account" wire:model="voucher.account_id" class="{{ $input }}">
                    @foreach ($this->cashAccounts as $a)
                        <option value="{{ $a['id'] }}">{{ $a['name'] }}</option>
                    @endforeach
                </select>
                @error('voucher.account_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
            </div>
            @if ($voucherType === 'salary_advance')
                <div class="flex flex-col gap-[6px]">
                    <label for="v-monthly" class="{{ $lbl }}">মাসে কাটবে (৳)</label>
                    <input id="v-monthly" type="number" step="0.01" min="0" wire:model="voucher.monthly" placeholder="2500" class="{{ $input }}">
                </div>
            @endif
        </div>

        @if (! in_array($voucherType, ['salary', 'salary_advance', 'kisti']))
            <div class="flex flex-col gap-[6px]">
                <label for="v-note" class="{{ $lbl }}">Note</label>
                <input id="v-note" type="text" wire:model="voucher.note" placeholder="optional" class="{{ $input }}">
            </div>
        @endif

        <x-slot:footer>
            <button type="button" wire:click="closeVoucher" class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
            <button type="button" wire:click="saveVoucher" wire:loading.attr="disabled" wire:target="saveVoucher"
                    class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[10px] text-[14px] font-bold text-white disabled:opacity-60">Save &amp; post</button>
        </x-slot:footer>
    </x-accounts.modal>
@endif
