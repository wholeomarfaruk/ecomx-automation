{{-- Voucher form modal — same design language as the panel (white, 14px radius, primary #4f46e5). --}}
<div>
@if ($open)
    @php
        $input = 'w-full text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
        $lbl = 'text-[13px] font-semibold';
        $p = $this->party;
        $title = ($editId ? 'Edit · ' : '') . ($this::TITLES[$type] ?? 'Voucher');
    @endphp
    <x-accounts.modal :title="$title" close="close" width="{{ $type === 'journal' ? 'max-w-[760px]' : 'max-w-[560px]' }}">

        @if (! $editId)
            <div class="flex flex-wrap gap-[6px]">
                @foreach ($this::TITLES as $k => $t)
                    <x-accounts.chip :on="$type === $k" wire:click="$set('type', '{{ $k }}')">{{ ['receipt' => 'টাকা আসলো', 'payment' => 'টাকা গেলো', 'expense' => 'Expense', 'contra' => 'Transfer', 'journal' => 'Journal'][$k] }}</x-accounts.chip>
                @endforeach
            </div>
        @endif

        @if (in_array($type, ['receipt', 'payment', 'expense', 'journal'], true))
            <div class="flex flex-col gap-[6px]">
                <span class="{{ $lbl }}">{{ $type === 'receipt' ? 'কার কাছ থেকে · From' : ($type === 'journal' ? 'Party (optional)' : 'কাকে · Paid to') }}</span>
                @if ($p)
                    <div class="flex items-center justify-between gap-3 rounded-[9px] border border-[#c7d2fe] bg-[#eef2ff] px-[12px] py-[8px]">
                        <div class="min-w-0"><div class="truncate font-semibold">{{ $p->display_name }}</div><div class="text-[11.5px] text-[#6b7280]">{{ $p->code }} · {{ $p->roles->pluck('role')->map(fn ($r) => ucfirst($r))->implode(', ') ?: 'No role' }}</div></div>
                        <button type="button" wire:click="clearParty" class="text-[12.5px] font-semibold text-[#4338ca]">Change</button>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-[8px] sm:grid-cols-2">
                        <div class="relative">
                            <input type="search" wire:model.live.debounce.300ms="partySearch" placeholder="Profile খুঁজুন (নাম / phone)" class="{{ $input }}">
                            @if ($this->partyMatches)
                                <div class="absolute z-20 mt-1 w-full overflow-hidden rounded-[9px] border border-[#e5e7eb] bg-white shadow-lg">
                                    @foreach ($this->partyMatches as $m)
                                        <button type="button" wire:click="pickParty({{ $m['id'] }})" class="flex w-full justify-between gap-2 border-t border-[#f0f1f4] px-[12px] py-[7px] text-left text-[13px] first:border-t-0 hover:bg-[#f9fafb]">
                                            <span class="font-semibold">{{ $m['name'] }}</span><span class="text-[11.5px] text-[#6b7280]">{{ $m['meta'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <input type="text" wire:model="f.party_label" placeholder="অথবা নাম লিখুন (যেমন Facebook Ads)" class="{{ $input }}">
                    </div>
                @endif
            </div>
        @endif

        @if ($type === 'journal')
            <div class="overflow-x-auto">
                <table class="min-w-[640px] text-[13px]">
                    <thead><tr class="text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280]"><th class="py-[6px] pr-[8px] font-semibold">Account</th><th class="px-[6px] py-[6px] font-semibold">Party</th><th class="px-[6px] py-[6px] text-right font-semibold">Debit</th><th class="px-[6px] py-[6px] text-right font-semibold">Credit</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $l)
                            <tr wire:key="jl-{{ $i }}" class="border-t border-[#f0f1f4]">
                                <td class="py-[6px] pr-[8px]">
                                    <select wire:model="lines.{{ $i }}.account_id" class="{{ $input }} py-[7px] text-[13px]">
                                        <option value="">Select account</option>
                                        @foreach ($this->ledgers as $group => $items)
                                            <optgroup label="{{ $group }}">@foreach ($items as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</optgroup>
                                        @endforeach
                                    </select>
                                    @error("lines.{$i}.account_id") <span class="text-[11.5px] text-[#b91c1c]">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-[6px] py-[6px]">
                                    <select wire:model="lines.{{ $i }}.profile_id" class="{{ $input }} w-[150px] py-[7px] text-[13px]">
                                        <option value="">—</option>
                                        @foreach ($this->profileOptions as $pid => $pname)<option value="{{ $pid }}">{{ $pname }}</option>@endforeach
                                        @if ($p && ! isset($this->profileOptions[$p->id]))<option value="{{ $p->id }}">{{ $p->display_name }}</option>@endif
                                    </select>
                                </td>
                                <td class="px-[6px] py-[6px]"><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.debit" class="{{ $input }} w-[120px] py-[7px] text-right text-[13px]"></td>
                                <td class="px-[6px] py-[6px]"><input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.credit" class="{{ $input }} w-[120px] py-[7px] text-right text-[13px]"></td>
                                <td class="py-[6px] pl-[4px]">@if (count($lines) > 2)<button type="button" wire:click="removeLine({{ $i }})" aria-label="Remove line" class="text-[#9ca3af] hover:text-[#b91c1c]">×</button>@endif</td>
                            </tr>
                        @endforeach
                        @php $t = $this->totals; @endphp
                        <tr class="border-t border-[#e5e7eb] bg-[#f9fafb] font-bold">
                            <td class="py-[7px] pl-[4px]" colspan="2"><button type="button" wire:click="addLine" class="font-semibold text-[#4338ca]">+ Add line</button>
                                <span class="ml-2 text-[12px] {{ $t['ok'] ? 'text-[#047857]' : 'text-[#b45309]' }}">{{ $t['ok'] ? '· balanced' : '· not balanced' }}</span></td>
                            <td class="px-[6px] py-[7px] text-right">{{ $money($t['dr']) }}</td><td class="px-[6px] py-[7px] text-right">{{ $money($t['cr']) }}</td><td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-[12px] text-[#6b7280]">Party দিতে প্রথমে উপরে কোনো profile বেছে নিন, অথবা receipt/payment form থেকে আসুন।</div>
        @else
            <div class="grid grid-cols-1 gap-[12px] sm:grid-cols-2">
                @if ($type === 'contra')
                    <div class="flex flex-col gap-[6px]"><label for="vf-from" class="{{ $lbl }}">From</label>
                        <select id="vf-from" wire:model.live="f.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach</select></div>
                    <div class="flex flex-col gap-[6px]"><label for="vf-to" class="{{ $lbl }}">To</label>
                        <select id="vf-to" wire:model.live="f.to_id" class="{{ $input }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach</select>
                        @error('f.to_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                @else
                    <div class="flex flex-col gap-[6px]"><label for="vf-acc" class="{{ $lbl }}">{{ $type === 'receipt' ? 'Received in' : 'Paid from' }}</label>
                        <select id="vf-acc" wire:model="f.account_id" class="{{ $input }}">@foreach ($this->cashAccounts as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }} · {{ $money($a['balance']) }}</option>@endforeach</select></div>
                    <div class="flex flex-col gap-[6px]"><label for="vf-ledger" class="{{ $lbl }}">{{ $type === 'expense' ? 'Category' : ($type === 'receipt' ? 'হিসাব · Credit to' : 'হিসাব · Debit to') }}</label>
                        <select id="vf-ledger" wire:model="f.ledger_id" class="{{ $input }}">
                            <option value="">Select</option>
                            @if ($type === 'expense')
                                @foreach ($this->categories as $c)<option value="{{ $c['id'] }}">{{ $c['name'] }}</option>@endforeach
                            @else
                                @foreach ($this->ledgers as $group => $items)<optgroup label="{{ $group }}">@foreach ($items as $a)<option value="{{ $a['id'] }}">{{ $a['name'] }}</option>@endforeach</optgroup>@endforeach
                            @endif
                        </select>
                        @error('f.ledger_id') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                @endif
                <div class="flex flex-col gap-[6px]"><label for="vf-amt" class="{{ $lbl }}">Amount (৳)</label><input id="vf-amt" type="number" min="0" step="0.01" wire:model="f.amount" class="{{ $input }}">
                    @error('f.amount') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                @if ($type === 'contra')
                    <div class="flex flex-col gap-[6px]"><label for="vf-charge" class="{{ $lbl }}">Charge (৳)</label><input id="vf-charge" type="number" min="0" step="0.01" wire:model="f.charge" placeholder="0" class="{{ $input }}"></div>
                @endif
                <div class="flex flex-col gap-[6px]"><label for="vf-date" class="{{ $lbl }}">Date</label><input id="vf-date" type="text" readonly placeholder="YYYY-MM-DD" wire:model="f.date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }}"></div>
            </div>
        @endif

        <div class="flex flex-col gap-[6px]"><label for="vf-note" class="{{ $lbl }}">Note</label><input id="vf-note" type="text" wire:model="f.note" placeholder="যেমন: Office rent, October" class="{{ $input }}"></div>

        <div x-data class="flex flex-col gap-[6px]">
            <span class="{{ $lbl }}">Attachment</span>
            <div class="flex flex-wrap items-center gap-[8px]">
                <input type="file" multiple x-ref="vf" wire:model="files" class="hidden" accept=".pdf,image/*,.xlsx,.csv">
                <button type="button" @click="$refs.vf.click()" class="rounded-[8px] border border-dashed border-[#c7d2fe] bg-white px-[12px] py-[7px] text-[13px] font-semibold text-[#4338ca]">+ Bill / slip</button>
                <span wire:loading wire:target="files" class="text-[12px] text-[#6b7280]">Uploading…</span>
                @foreach ($files as $file)<span class="rounded-[6px] bg-[#eef2ff] px-[8px] py-[2px] text-[12px] text-[#3730a3]">{{ $file->getClientOriginalName() }}</span>@endforeach
            </div>
            @if ($attachAbove)<span class="text-[11.5px] text-[#6b7280]">{{ $money($attachAbove) }}-এর বেশি হলে attachment ছাড়া post হবে না।</span>@endif
            @error('files.*') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror
        </div>

        <x-slot:footer>
            <button type="button" wire:click="close" class="mr-auto rounded-[9px] border border-[#e5e7eb] bg-white px-[16px] py-[10px] text-[14px] text-[#374151]">Cancel</button>
            <button type="button" wire:click="save(false)" wire:loading.attr="disabled" class="rounded-[9px] border border-[#c7d2fe] bg-white px-[16px] py-[10px] text-[14px] font-semibold text-[#3730a3]">Save as draft</button>
            @if ($canPost)
                <button type="button" wire:click="save(true)" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[20px] py-[10px] text-[14px] font-bold text-white">Save &amp; post</button>
            @endif
        </x-slot:footer>
    </x-accounts.modal>
@endif
</div>
