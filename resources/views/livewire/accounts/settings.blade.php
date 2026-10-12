{{-- Accounts settings — design: Settings.dc.html --}}
@php
    $field = 'flex flex-col gap-[6px]';
    $lbl = 'text-[13px] font-semibold';
    $input = 'text-[14px] px-[12px] py-[10px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
    $select = 'text-[14px] pl-[10px] pr-[30px] py-[10px] border border-[#d1d5db] rounded-[9px] bg-white';
    $small = 'text-[13px] px-[9px] py-[7px] border border-[#d1d5db] rounded-[8px]';
    $head = 'border-b border-[#f0f1f4] px-[22px] py-[18px]';
    $th = 'text-left text-[11.5px] uppercase tracking-[0.04em] text-[#6b7280] bg-[#f9fafb]';
    $canSave = \App\Support\AccountsAccess::can('settings');
@endphp

<div class="acc-profiles box-border flex w-full max-w-[1280px] flex-col gap-[18px]">

    <div>
        <h1 class="m-0 text-[24px] font-bold">সেটিংস · Accounts settings</h1>
        <div class="mt-[2px] text-[13px] text-[#6b7280]">হিসাবের নিয়ম, voucher number, কে কী করতে পারবে</div>
    </div>

    <div class="flex flex-wrap items-start gap-[16px]">

        <nav aria-label="Settings sections" class="flex max-w-full flex-[1_1_230px] flex-col gap-[2px] rounded-[14px] border border-[#e5e7eb] bg-white p-[10px]">
            @foreach ($this::SECTIONS as $key => [$label, $sub])
                <button type="button" wire:click="setSection('{{ $key }}')"
                        @class(['rounded-[9px] border-0 px-[12px] py-[10px] text-left text-[13.5px]',
                                'bg-[#eef2ff] font-bold text-[#312e81]' => $section === $key,
                                'bg-transparent font-semibold text-[#111827] hover:bg-[#f9fafb]' => $section !== $key])>
                    <span class="block">{{ $label }}</span><span class="mt-[1px] block text-[11.5px] font-normal text-[#6b7280]">{{ $sub }}</span>
                </button>
            @endforeach
        </nav>

        <section class="min-w-0 flex-[999_1_600px] overflow-hidden rounded-[14px] border border-[#e5e7eb] bg-white">

            @if ($section === 'general')
                <div class="{{ $head }}"><h2 class="m-0 text-[17px] font-bold">General</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">ব্যবসার তথ্য আর হিসাবের বছর</div></div>
                <div class="grid gap-[16px] px-[22px] py-[18px] [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
                    <div class="{{ $field }}"><label for="g-name" class="{{ $lbl }}">Business name</label><input id="g-name" type="text" wire:model="general.business_name" class="{{ $input }}">
                        @error('general.business_name') <span class="text-[12px] text-[#b91c1c]">{{ $message }}</span> @enderror</div>
                    <div class="{{ $field }}"><label for="g-bin" class="{{ $lbl }}">BIN / TIN (invoice-এ দেখাবে)</label><input id="g-bin" type="text" wire:model="general.bin" placeholder="optional" class="{{ $input }}"></div>
                    <div class="{{ $field }}"><label for="g-fy" class="{{ $lbl }}">Financial year</label>
                        <select id="g-fy" wire:model="general.fy_start_month" class="{{ $select }}"><option value="7">July – June (Bangladesh)</option><option value="1">January – December</option></select></div>
                    <div class="{{ $field }}"><label for="g-week" class="{{ $lbl }}">Week starts on</label>
                        <select id="g-week" wire:model="general.week_start" class="{{ $select }}"><option value="6">Saturday</option><option value="0">Sunday</option><option value="1">Monday</option></select></div>
                    <div class="{{ $field }}"><label for="g-cur" class="{{ $lbl }}">Currency &amp; format</label>
                        <select id="g-cur" wire:model="general.currency_format" class="{{ $select }}"><option value="lakh">BDT · ৳1,24,300 (lakh)</option><option value="international">BDT · ৳124,300 (international)</option></select></div>
                    <div class="{{ $field }}"><label for="g-tz" class="{{ $lbl }}">Timezone</label>
                        <select id="g-tz" class="{{ $select }}" disabled><option>{{ config('app.timezone') }} (UTC{{ now()->format('P') === '+06:00' ? '+6' : now()->format('P') }})</option></select></div>
                    <div class="{{ $field }}"><label for="g-start" class="{{ $lbl }}">Books start date</label><input id="g-start" type="date" wire:model="general.books_start" class="{{ $input }} py-[9px]">
                        <span class="text-[12px] text-[#6b7280]">এর আগের হিসাব opening balance হিসেবে আসবে</span></div>
                    <div class="{{ $field }}"><label for="g-lock" class="{{ $lbl }}">Books locked up to</label><input id="g-lock" type="date" wire:model="general.lock_date" class="{{ $input }} py-[9px]">
                        <span class="text-[12px] text-[#6b7280]">এই তারিখ পর্যন্ত কিছু post বা edit করা যাবে না</span></div>
                </div>
            @endif

            @if ($section === 'posting')
                <div class="{{ $head }}"><h2 class="m-0 text-[17px] font-bold">Posting rules</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">কোন entry কে post করতে পারবে, কখন approval লাগবে</div></div>
                <div class="flex flex-col px-[22px] pb-[18px] pt-[8px]">
                    @foreach ($this::RULE_TEXT as $key => [$title, $desc])
                        @php $on = $rules[$key]['on']; @endphp
                        <div class="flex flex-wrap items-center justify-between gap-[12px] border-t border-[#f0f1f4] py-[14px] first:border-t-0">
                            <div class="flex-[1_1_300px]"><div class="font-semibold">{{ $title }}</div><div class="mt-[2px] text-[12.5px] text-[#6b7280]">{{ $desc }}</div></div>
                            <div class="flex items-center gap-[10px]">
                                @if (in_array($key, ['approval', 'attach'], true))
                                    <label class="flex items-center gap-[6px] text-[13px]">৳<input type="number" min="0" wire:model="rules.{{ $key }}.amount" aria-label="Amount limit" class="{{ $small }} w-[100px]"></label>
                                @endif
                                <x-accounts.toggle :on="$on" :label="$title" wire:click="toggleRule('{{ $key }}')" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($section === 'numbering')
                <div class="{{ $head }}"><h2 class="m-0 text-[17px] font-bold">Voucher numbering</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">প্রতিটা voucher type-এর prefix আর পরের নম্বর</div></div>
                <div class="overflow-x-auto">
                    <table class="min-w-[560px] text-[13.5px]">
                        <thead><tr class="{{ $th }}"><th class="px-[22px] py-[10px] font-semibold">Type</th><th class="px-[12px] py-[10px] font-semibold">Prefix</th><th class="px-[12px] py-[10px] font-semibold">Next no.</th><th class="px-[22px] py-[10px] font-semibold">Preview</th></tr></thead>
                        <tbody>
                            @foreach (\App\Support\AccountsSettings::VOUCHER_TYPES as $type => [$label])
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="px-[22px] py-[9px] font-semibold">{{ $label }}</td>
                                    <td class="px-[12px] py-[7px]"><input type="text" wire:model.live.debounce.300ms="numbering.{{ $type }}.prefix" aria-label="{{ $label }} prefix" class="{{ $small }} w-[80px]"></td>
                                    <td class="px-[12px] py-[7px]"><input type="number" min="1" wire:model.live.debounce.300ms="numbering.{{ $type }}.next" aria-label="{{ $label }} next number" class="{{ $small }} w-[90px]"></td>
                                    <td class="px-[22px] py-[9px] font-semibold text-[#4338ca]">{{ $numbering[$type]['prefix'] }}{{ str_pad((string) (int) $numbering[$type]['next'], 4, '0', STR_PAD_LEFT) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('numbering.*') <div class="px-[22px] pt-[8px] text-[12px] text-[#b91c1c]">{{ $message }}</div> @enderror
                <div class="flex items-center gap-[8px] px-[22px] pb-[16px] pt-[12px] text-[13px]"><input id="nb-reset" type="checkbox" wire:model="resetEachFy" class="rounded text-[#4f46e5]"><label for="nb-reset">নতুন financial year-এ নম্বর 0001 থেকে শুরু হবে</label></div>
            @endif

            @if ($section === 'categories')
                <div class="{{ $head }} flex flex-wrap items-center justify-between gap-[10px]">
                    <div><h2 class="m-0 text-[17px] font-bold">Expense categories &amp; budgets</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Expenses page-এর category আর মাসিক budget</div></div>
                    <div x-data="{ open: false }" class="flex items-center gap-[6px]">
                        <input x-show="open" x-cloak type="text" wire:model="newCategory" wire:keydown.enter="addCategory" placeholder="Category name" class="{{ $small }} w-[180px]">
                        <button type="button" @click="open ? $wire.addCategory() : (open = true)" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[12px] py-[8px] text-[13px] text-[#374151]">+ Category</button>
                    </div>
                </div>
                @error('newCategory') <div class="px-[22px] pt-[8px] text-[12px] text-[#b91c1c]">{{ $message }}</div> @enderror
                <div class="overflow-x-auto">
                    <table class="min-w-[520px] text-[13.5px]">
                        <thead><tr class="{{ $th }}"><th class="px-[22px] py-[10px] font-semibold">Category</th><th class="px-[12px] py-[10px] font-semibold">Monthly budget</th><th class="px-[22px] py-[10px] font-semibold">Receipt required above</th></tr></thead>
                        <tbody>
                            @forelse ($categories as $i => $c)
                                <tr wire:key="cat-{{ $c['id'] }}" class="border-t border-[#f0f1f4]">
                                    <td class="px-[22px] py-[9px] font-semibold">{{ $c['name'] }}</td>
                                    <td class="px-[12px] py-[7px]"><label class="flex items-center gap-[6px]">৳<input type="number" min="0" wire:model="categories.{{ $i }}.budget" aria-label="{{ $c['name'] }} budget" class="{{ $small }} w-[110px]"></label></td>
                                    <td class="px-[22px] py-[9px] text-[#6b7280]">{{ $globalReceipt ? \App\Support\Bdt::money($globalReceipt) . ' (global rule)' : 'Not required' }}</td>
                                </tr>
                            @empty
                                <tr class="border-t border-[#f0f1f4]"><td colspan="3" class="px-[22px] py-[18px] text-center text-[#6b7280]">কোনো category নেই।</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($section === 'users')
                <div class="{{ $head }} flex flex-wrap items-center justify-between gap-[10px]">
                    <div><h2 class="m-0 text-[17px] font-bold">Users &amp; permissions</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Role অনুযায়ী কে কী করতে পারবে · login দেওয়া হয় Employee profile থেকে</div></div>
                    <a href="{{ route('accounts.profiles.employees') }}" wire:navigate class="text-[13px] font-semibold">Employees</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[640px] text-[13px]">
                        <thead><tr class="{{ $th }}"><th class="px-[22px] py-[10px] font-semibold">Permission</th>
                            @foreach ($this::ROLES as $role => $label)<th class="px-[12px] py-[10px] text-center font-semibold {{ $loop->last ? 'pr-[22px]' : '' }}">{{ $label }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($this::PERMS as $perm => $label)
                                <tr class="border-t border-[#f0f1f4]">
                                    <td class="px-[22px] py-[10px]">{{ $label }}</td>
                                    @foreach ($this::ROLES as $role => $roleLabel)
                                        <td class="px-[12px] py-[8px] text-center"><input type="checkbox" wire:model="matrix.{{ $perm }}.{{ $role }}" aria-label="{{ $roleLabel }}: {{ $label }}" class="h-[16px] w-[16px] rounded text-[#4f46e5]"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-[22px] pb-[16px] pt-[12px] text-[12.5px] text-[#6b7280]">Users: {{ $this->usersLine }}</div>
            @endif

            @if ($section === 'sms')
                <div class="{{ $head }}"><h2 class="m-0 text-[17px] font-bold">SMS &amp; reminders</h2><div class="mt-[2px] text-[12.5px] text-[#6b7280]">Customer-কে কিস্তি ও বাকির reminder, নিজেদের alert</div></div>
                <div class="flex flex-col gap-[16px] px-[22px] py-[18px]">
                    <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
                        <div class="{{ $field }}"><label for="s-gw" class="{{ $lbl }}">SMS gateway</label>
                            <select id="s-gw" wire:model="sms.gateway" class="{{ $select }}">@foreach ($this::GATEWAYS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                        <div class="{{ $field }}"><label for="s-mask" class="{{ $lbl }}">Sender ID / mask</label><input id="s-mask" type="text" wire:model="sms.mask" class="{{ $input }}"></div>
                    </div>
                    <div class="{{ $field }}"><label for="s-tpl1" class="{{ $lbl }}">কিস্তির reminder (due-এর আগের দিন)</label>
                        <textarea id="s-tpl1" rows="2" wire:model="sms.kisti" class="{{ $input }} resize-y text-[13.5px]"></textarea></div>
                    <div class="{{ $field }}"><label for="s-tpl2" class="{{ $lbl }}">বাকির reminder</label>
                        <textarea id="s-tpl2" rows="2" wire:model="sms.due" class="{{ $input }} resize-y text-[13.5px]"></textarea>
                        <span class="text-[12px] text-[#6b7280]">Variables: {name} {amount} {date} {due} {kisti_no}</span></div>
                    <div class="flex flex-col gap-[10px]">
                        <span class="text-[13px] font-semibold">নিজেদের alert</span>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="sms.stale_voucher" class="rounded text-[#4f46e5]"> Voucher ৪৮ ঘণ্টার বেশি post না হলে Accountant-কে জানাও</label>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="sms.emi_3_days" class="rounded text-[#4f46e5]"> Loan EMI-র ৩ দিন আগে Owner-কে জানাও</label>
                        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="sms.daily_summary" class="rounded text-[#4f46e5]"> প্রতিদিন রাত ৯টায় দিনের হিসাবের summary</label>
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-[8px] border-t border-[#f0f1f4] bg-[#fcfcfd] px-[22px] py-[14px]">
                @unless ($canSave)<span class="mr-auto self-center text-[12.5px] text-[#b45309]">Settings বদলানোর permission নেই।</span>@endunless
                <button type="button" wire:click="discard" class="rounded-[8px] border border-[#e5e7eb] bg-white px-[14px] py-[9px] text-[13px] text-[#374151]">Discard</button>
                <button type="button" wire:click="save" wire:loading.attr="disabled" @disabled(! $canSave) class="rounded-[8px] border-0 bg-[#4f46e5] px-[18px] py-[9px] text-[13px] font-semibold text-white disabled:opacity-50">Save changes</button>
            </div>
        </section>
    </div>
</div>
