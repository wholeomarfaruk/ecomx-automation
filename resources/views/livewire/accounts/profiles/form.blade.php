{{-- New / edit profile — design: ProfileForm.dc.html --}}
@php
    $isBusiness = $type === 'business';
    $dup = $this->duplicate;
    $in = 'text-[14px] px-[12px] py-[10px] border rounded-[9px] focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
    $input = $in . ' border-[#d1d5db]';
    $roleIn = 'text-[14px] px-[12px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white focus:border-[#4f46e5] focus:ring-2 focus:ring-[#e0e7ff]';
    $roleSel = 'text-[14px] pl-[10px] pr-[30px] py-[9px] border border-[#d1d5db] rounded-[9px] bg-white';
    $lbl = 'text-[13px] font-semibold';
    $section = 'flex flex-col gap-[14px] rounded-[14px] border border-[#e5e7eb] bg-white px-[20px] py-[18px]';
    $seg = fn ($on) => $on
        ? 'rounded-[7px] border-0 bg-white px-[16px] py-[8px] text-[13px] font-bold text-[#312e81] shadow-[0_1px_2px_rgba(0,0,0,0.08)]'
        : 'rounded-[7px] border-0 bg-transparent px-[16px] py-[8px] text-[13px] text-[#4b5563]';
    $roleDefs = [
        'customer' => ['Customer', 'আমার কাছ থেকে কেনে · পাবো', '#10b981', '#f0fdf4'],
        'supplier' => ['Supplier', 'আমি তার কাছ থেকে কিনি · দিবো', '#0ea5e9', '#f0f9ff'],
        'lender'   => ['Lender', 'ঋণ দিয়েছে · কিস্তি দিবো', '#6366f1', '#eef2ff'],
        'owner'    => ['Owner', 'মূলধন ও লাভের ভাগ', '#f59e0b', '#fffbeb'],
        'employee' => ['Employee', 'বেতন, অগ্রিম ও login', '#14b8a6', '#f0fdfa'],
    ];
    $err = fn ($field) => $errors->first($field);
    $opening = $this->openingEntry;
@endphp

<form wire:submit="save" class="acc-profiles box-border flex w-full max-w-[960px] flex-col gap-[16px]">
    <x-accounts.profile-tabs active="profiles" />

    <a href="{{ $profile ? route('accounts.profiles.show', $profile) : route('accounts.profiles.index') }}" wire:navigate class="flex w-max items-center gap-[6px] text-[13px]">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
        {{ $profile ? $profile->display_name : 'Master Profiles' }}</a>

    <div>
        <h1 class="m-0 text-[24px] font-bold">{{ $profile ? 'Edit profile' : 'New profile' }}</h1>
        <div class="mt-[2px] text-[13px] text-[#6b7280]">একবার profile বানান, তারপর যত role দরকার দিন। Code: {{ $this->nextCode }}{{ $profile ? '' : ' (auto)' }}</div>
    </div>

    {{-- Basic --}}
    <section class="{{ $section }}">
        <h2 class="m-0 text-[15px] font-bold">মূল তথ্য · Basic</h2>
        <div role="group" aria-label="Profile type" class="flex w-max gap-[4px] rounded-[10px] bg-[#f3f4f6] p-[4px]">
            <button type="button" wire:click="$set('type', 'individual')" class="{{ $seg(! $isBusiness) }}">Individual</button>
            <button type="button" wire:click="$set('type', 'business')" class="{{ $seg($isBusiness) }}">Business</button>
        </div>
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
            <div class="flex flex-col gap-[6px]">
                <label for="f-name" class="{{ $lbl }}">{{ $isBusiness ? 'Business name' : 'Full name' }} <span class="text-[#b91c1c]">*</span></label>
                <input id="f-name" type="text" wire:model="name" class="{{ $input }}">
                @if ($err('name')) <span class="text-[12px] text-[#b91c1c]">{{ $err('name') }}</span> @endif
            </div>
            @if ($isBusiness)
                <div class="flex flex-col gap-[6px]">
                    <label for="f-contact" class="{{ $lbl }}">Contact person</label>
                    <input id="f-contact" type="text" wire:model="contact_person" placeholder="যার সাথে কথা হয়" class="{{ $input }}">
                </div>
            @endif
            <div class="flex flex-col gap-[6px]">
                <label for="f-phone" class="{{ $lbl }}">Mobile <span class="text-[#b91c1c]">*</span></label>
                <input id="f-phone" type="tel" wire:model.live.debounce.500ms="phone" placeholder="01XXXXXXXXX"
                       class="{{ $in }} {{ $dup ? 'border-[#f59e0b] bg-[#fffbeb]' : 'border-[#d1d5db]' }}">
                @if ($err('phone')) <span class="text-[12px] text-[#b91c1c]">{{ $err('phone') }}</span> @endif
            </div>
            <div class="flex flex-col gap-[6px]">
                <label for="f-alt" class="{{ $lbl }}">Alt mobile</label>
                <input id="f-alt" type="tel" wire:model.live.debounce.500ms="alt_phone" placeholder="01XXXXXXXXX" class="{{ $input }}">
                @if ($err('alt_phone')) <span class="text-[12px] text-[#b91c1c]">{{ $err('alt_phone') }}</span> @endif
            </div>
            <div class="flex flex-col gap-[6px]">
                <label for="f-email" class="{{ $lbl }}">Email</label>
                <input id="f-email" type="email" wire:model="email" placeholder="optional" class="{{ $input }}">
                @if ($err('email')) <span class="text-[12px] text-[#b91c1c]">{{ $err('email') }}</span> @endif
            </div>
        </div>
        @if ($dup)
            @php $addRole = collect(array_keys(array_filter($roles)))->first(fn ($r) => ! $dup->hasRole($r)); @endphp
            <div role="alert" class="flex flex-wrap items-center justify-between gap-[10px] rounded-[10px] border border-[#fde68a] bg-[#fffbeb] px-[14px] py-[10px] text-[13px] text-[#78350f]">
                <span>এই নম্বরে আগে থেকেই profile আছে: <b>{{ $dup->display_name }} ({{ $dup->code }})</b>{{ $dup->roles->isNotEmpty() ? ' · ' . $dup->roles->pluck('role')->map(fn ($r) => ucfirst($r))->implode(', ') : '' }}</span>
                <span class="flex gap-[12px]">
                    <a href="{{ route('accounts.profiles.show', $dup) }}" wire:navigate class="font-semibold !text-[#92400e]">Open profile</a>
                    <a href="{{ route('accounts.profiles.edit', ['profile' => $dup, 'role' => $addRole ?: ($presetRole ?: null)]) }}" wire:navigate class="font-semibold !text-[#92400e]">ওখানে role যোগ করুন</a>
                </span>
            </div>
        @endif
    </section>

    {{-- Roles --}}
    <section class="{{ $section }}">
        <div>
            <h2 class="m-0 text-[15px] font-bold">Role · কী হিসেবে লেনদেন হবে</h2>
            <div class="mt-[2px] text-[12.5px] text-[#6b7280]">একাধিক select করা যায়। প্রতিটা role-এর নিজের setting।</div>
        </div>
        <div class="grid gap-[10px] [grid-template-columns:repeat(auto-fit,minmax(190px,1fr))]">
            @foreach ($roleDefs as $key => [$label, $desc, $color, $bg])
                @php $on = $roles[$key]; @endphp
                <label class="flex cursor-pointer items-start gap-[10px] rounded-[12px] border-[1.5px] px-[14px] py-[12px]"
                       style="border-color: {{ $on ? $color : '#e5e7eb' }}; background: {{ $on ? $bg : '#fff' }}">
                    <input type="checkbox" wire:model.live="roles.{{ $key }}" class="mt-[3px] h-[16px] w-[16px] rounded accent-[#4f46e5] text-[#4f46e5]">
                    <span><span class="block font-bold">{{ $label }}</span><span class="mt-[2px] block text-[12px] text-[#6b7280]">{{ $desc }}</span></span>
                </label>
            @endforeach
        </div>

        @if ($roles['customer'])
            <div class="flex flex-col gap-[12px] rounded-[12px] border border-[#a7f3d0] bg-[#f0fdf4] px-[16px] py-[14px]">
                <div class="font-bold text-[#065f46]">Customer settings</div>
                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]"><label for="c-limit" class="{{ $lbl }}">Credit limit (৳)</label><input id="c-limit" type="number" min="0" wire:model="customer.credit_limit" placeholder="30000" class="{{ $roleIn }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="c-terms" class="{{ $lbl }}">বাকি পরিশোধের সময়</label>
                        <select id="c-terms" wire:model="customer.terms_days" class="{{ $roleSel }}"><option value="cash">Cash only</option><option value="7">7 days</option><option value="15">15 days</option><option value="30">30 days</option></select></div>
                    <div class="flex flex-col gap-[6px]"><label for="c-price" class="{{ $lbl }}">Price group</label>
                        <select id="c-price" wire:model="customer.price_group" class="{{ $roleSel }}"><option value="retail">Retail</option><option value="wholesale">Wholesale</option></select></div>
                </div>
                <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="customer.allow_kisti" class="rounded text-[#4f46e5]"> কিস্তিতে বিক্রি করা যাবে (EMI allowed)</label>
            </div>
        @endif

        @if ($roles['supplier'])
            <div class="flex flex-col gap-[12px] rounded-[12px] border border-[#bae6fd] bg-[#f0f9ff] px-[16px] py-[14px]">
                <div class="font-bold text-[#075985]">Supplier settings</div>
                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]"><label for="s-cat" class="{{ $lbl }}">Category</label>
                        <select id="s-cat" wire:model="supplier.category" class="{{ $roleSel }}">
                            @foreach (\App\Livewire\Accounts\Profiles\Suppliers::CATEGORIES as $c)<option>{{ $c }}</option>@endforeach
                        </select></div>
                    <div class="flex flex-col gap-[6px]"><label for="s-terms" class="{{ $lbl }}">Payment terms</label>
                        <select id="s-terms" wire:model="supplier.terms" class="{{ $roleSel }}">
                            @foreach (\App\Livewire\Accounts\Profiles\Suppliers::TERMS as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach
                        </select></div>
                    <div class="flex flex-col gap-[6px]"><label for="s-acc" class="{{ $lbl }}">Payment to (bKash / bank)</label><input id="s-acc" type="text" wire:model="supplier.payout_account" placeholder="bKash 01XXXXXXXXX বা A/C no." class="{{ $roleIn }}"></div>
                </div>
            </div>
        @endif

        @if ($roles['lender'])
            <div class="flex flex-col gap-[12px] rounded-[12px] border border-[#c7d2fe] bg-[#eef2ff] px-[16px] py-[14px]">
                <div class="font-bold text-[#3730a3]">Lender settings</div>
                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]"><label for="l-type" class="{{ $lbl }}">Lender type</label>
                        <select id="l-type" wire:model="lender.lender_type" class="{{ $roleSel }}"><option value="bank">Bank / NBFI</option><option value="business">Business</option><option value="personal">Personal</option></select></div>
                </div>
                <div class="text-[12.5px] text-[#3730a3]">Save করার পর Loans &amp; EMI-তে ঋণের পরিমাণ, কিস্তির নিয়ম (Monthly / Weekly / Daily / Yearly / Custom) আর support setting দেবেন।</div>
            </div>
        @endif

        @if ($roles['owner'])
            <div class="flex flex-col gap-[12px] rounded-[12px] border border-[#fde68a] bg-[#fffbeb] px-[16px] py-[14px]">
                <div class="font-bold text-[#92400e]">Owner settings</div>
                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]"><label for="o-share" class="{{ $lbl }}">Profit share (%)</label><input id="o-share" type="number" min="0" max="100" step="0.01" wire:model="owner.share_percent" placeholder="40" class="{{ $roleIn }}">
                        @if ($err('owner.share_percent')) <span class="text-[12px] text-[#b91c1c]">{{ $err('owner.share_percent') }}</span> @endif</div>
                    <div class="flex flex-col gap-[6px]"><label for="o-cap" class="{{ $lbl }}">Opening capital (৳)</label><input id="o-cap" type="number" min="0" wire:model="owner.opening_capital" placeholder="500000" class="{{ $roleIn }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="o-into" class="{{ $lbl }}">টাকা কোথায় এসেছে</label>
                        <select id="o-into" wire:model="owner.capital_into" class="{{ $roleIn }}">
                            @foreach (app(\App\Services\Profiles\LedgerAccounts::class)->cashAccounts() as $acc)<option value="{{ $acc['id'] }}">{{ $acc['name'] }}</option>@endforeach
                            <option value="books">আগেই হিসাবে আছে (opening balance)</option>
                        </select>
                        <span class="text-[11.5px] text-[#6b7280]">Cash / bank বাছলে ঐ account-এর balance বাড়বে।</span></div>
                </div>
            </div>
        @endif

        @if ($roles['employee'])
            <div class="flex flex-col gap-[12px] rounded-[12px] border border-[#99f6e4] bg-[#f0fdfa] px-[16px] py-[14px]">
                <div class="font-bold text-[#115e59]">Employee settings</div>
                <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                    <div class="flex flex-col gap-[6px]"><label for="e-desig" class="{{ $lbl }}">Designation</label><input id="e-desig" type="text" wire:model="employee.designation" placeholder="Sales executive" class="{{ $roleIn }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="e-dept" class="{{ $lbl }}">Department</label>
                        <select id="e-dept" wire:model="employee.department" class="{{ $roleSel }}">
                            @foreach (\App\Livewire\Accounts\Profiles\Employees::DEPARTMENTS as $d)<option>{{ $d }}</option>@endforeach
                        </select></div>
                    <div class="flex flex-col gap-[6px]"><label for="e-join" class="{{ $lbl }}">Joining date</label><input id="e-join" type="text" readonly placeholder="YYYY-MM-DD" wire:model="employee.joined_on" class="flatpickr-only-date cursor-pointer bg-white {{ $roleIn }} py-[8px]"></div>
                    <div class="flex flex-col gap-[6px]"><label for="e-sal" class="{{ $lbl }}">Monthly salary (৳)</label><input id="e-sal" type="number" min="0" wire:model="employee.salary" placeholder="16000" class="{{ $roleIn }}"></div>
                    <div class="flex flex-col gap-[6px]"><label for="e-pay" class="{{ $lbl }}">Salary paid via</label>
                        <select id="e-pay" wire:model="employee.pay_via" class="{{ $roleSel }}"><option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="bank">Bank</option></select></div>
                    <div class="flex flex-col gap-[6px]"><label for="e-acc" class="{{ $lbl }}">bKash / bank no.</label><input id="e-acc" type="text" wire:model="employee.pay_account" placeholder="optional" class="{{ $roleIn }}"></div>
                </div>
                @if ($profile?->user)
                    <div class="text-[13px] text-[#115e59]">Software login আছে: {{ $profile->user->email }}</div>
                @else
                    <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model.live="employee.give_login" class="rounded text-[#4f46e5]"> Software login দিন (role: Accountant / Sales / Inventory)</label>
                    @if ($employee['give_login'])
                        <div class="grid gap-[12px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
                            <div class="flex flex-col gap-[6px]"><label for="e-role" class="{{ $lbl }}">Login role</label>
                                <select id="e-role" wire:model="employee.login_role" class="{{ $roleSel }}">
                                    <option value="">Select role</option>
                                    @foreach ($this->loginRoles as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
                                </select>
                                @if ($err('employee.login_role')) <span class="text-[12px] text-[#b91c1c]">{{ $err('employee.login_role') }}</span> @endif</div>
                            <div class="flex flex-col gap-[6px]"><label for="e-email" class="{{ $lbl }}">Login email</label><input id="e-email" type="email" wire:model="employee.login_email" class="{{ $roleIn }}">
                                @if ($err('employee.login_email')) <span class="text-[12px] text-[#b91c1c]">{{ $err('employee.login_email') }}</span> @endif</div>
                            <div class="flex flex-col gap-[6px]"><label for="e-pass" class="{{ $lbl }}">Password</label><input id="e-pass" type="password" wire:model="employee.login_password" autocomplete="new-password" class="{{ $roleIn }}">
                                @if ($err('employee.login_password')) <span class="text-[12px] text-[#b91c1c]">{{ $err('employee.login_password') }}</span> @endif</div>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </section>

    {{-- Opening balance --}}
    <section class="{{ $section }}">
        <div>
            <h2 class="m-0 text-[15px] font-bold">শুরুর বাকি · Opening balance</h2>
            <div class="mt-[2px] text-[12.5px] text-[#6b7280]">System চালুর আগের হিসাব থাকলে দিন। না থাকলে ফাঁকা রাখুন।</div>
        </div>
        @if ($opening)
            @php $obDr = (float) $opening->lines->sum('debit'); $isReceive = $opening->purpose === 'opening_receive'; @endphp
            <div class="rounded-[10px] border border-[#eef0f3] bg-[#f9fafb] px-[14px] py-[10px] text-[13px] text-[#374151]">
                Posted: <b>{{ \App\Support\Bdt::money($obDr) }} {{ $isReceive ? 'Dr (আমি পাবো)' : 'Cr (আমি দিবো)' }}</b> · {{ $opening->entry_date->format('d M Y') }} · {{ $opening->entry_number }}.
                সংশোধনের জন্য reversing journal দিন।
            </div>
        @else
            <div class="grid items-end gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(200px,1fr))]">
                <div class="flex flex-col gap-[6px]"><label for="ob-amt" class="{{ $lbl }}">Amount (৳)</label><input id="ob-amt" type="number" min="0" step="0.01" wire:model="ob_amount" placeholder="0" class="{{ $input }}">
                    @if ($err('ob_amount')) <span class="text-[12px] text-[#b91c1c]">{{ $err('ob_amount') }}</span> @endif</div>
                <div role="radiogroup" aria-label="Balance type" class="flex gap-[4px] rounded-[10px] bg-[#f3f4f6] p-[4px]">
                    <button type="button" wire:click="$set('ob_direction', 'receive')" class="{{ $seg($ob_direction === 'receive') }} flex-1">আমি পাবো</button>
                    <button type="button" wire:click="$set('ob_direction', 'pay')" class="{{ $seg($ob_direction === 'pay') }} flex-1">আমি দিবো</button>
                </div>
                <div class="flex flex-col gap-[6px]"><label for="ob-date" class="{{ $lbl }}">As of date</label><input id="ob-date" type="text" readonly placeholder="YYYY-MM-DD" wire:model="ob_date" class="flatpickr-only-date cursor-pointer bg-white {{ $input }} py-[9px]"></div>
            </div>
        @endif
    </section>

    {{-- Address --}}
    <section class="{{ $section }}">
        <h2 class="m-0 text-[15px] font-bold">ঠিকানা · Address</h2>
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
            <div class="flex flex-col gap-[6px]"><label for="a-div" class="{{ $lbl }}">Division</label>
                <select id="a-div" wire:model.live="state_id" class="{{ $roleSel }}">
                    <option value="">Select</option>
                    @foreach ($this->divisions as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach
                </select></div>
            <div class="flex flex-col gap-[6px]"><label for="a-dis" class="{{ $lbl }}">District</label>
                <select id="a-dis" wire:model.live="city_id" wire:key="dis-{{ $state_id }}" class="{{ $roleSel }}" @disabled(! $state_id)>
                    <option value="">Select</option>
                    @foreach ($this->districtOptions as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach
                </select></div>
            <div class="flex flex-col gap-[6px]"><label for="a-up" class="{{ $lbl }}">Upazila / Thana</label>
                <select id="a-up" wire:model="ps_id" wire:key="ps-{{ $city_id }}" class="{{ $roleSel }}" @disabled(! $city_id)>
                    <option value="">Select</option>
                    @foreach ($this->thanaOptions as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach
                </select></div>
        </div>
        <div class="flex flex-col gap-[6px]"><label for="a-line" class="{{ $lbl }}">Address</label><input id="a-line" type="text" wire:model="address" placeholder="House, road, area" class="{{ $input }}"></div>
    </section>

    {{-- Identity --}}
    <section class="{{ $section }}">
        <h2 class="m-0 text-[15px] font-bold">পরিচয় ও কাগজ · Identity &amp; documents</h2>
        <div class="grid gap-[14px] [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
            <div class="flex flex-col gap-[6px]"><label for="i-nid" class="{{ $lbl }}">NID</label><input id="i-nid" type="text" wire:model="nid" placeholder="10 or 17 digits" class="{{ $input }}">
                @if ($err('nid')) <span class="text-[12px] text-[#b91c1c]">{{ $err('nid') }}</span> @endif</div>
            <div class="flex flex-col gap-[6px]"><label for="i-tin" class="{{ $lbl }}">TIN</label><input id="i-tin" type="text" wire:model="tin" placeholder="12 digits" class="{{ $input }}">
                @if ($err('tin')) <span class="text-[12px] text-[#b91c1c]">{{ $err('tin') }}</span> @endif</div>
            <div class="flex flex-col gap-[6px]"><label for="i-bin" class="{{ $lbl }}">BIN (VAT)</label><input id="i-bin" type="text" wire:model="bin" placeholder="optional" class="{{ $input }}"></div>
            <div class="flex flex-col gap-[6px]"><label for="i-tl" class="{{ $lbl }}">Trade license</label><input id="i-tl" type="text" wire:model="trade_license" placeholder="optional" class="{{ $input }}"></div>
        </div>
        <div x-data class="rounded-[12px] border-[1.5px] border-dashed border-[#c7d2fe] p-[18px] text-center text-[13px] text-[#4b5563]">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="inline"><path d="M12 16V4"></path><path d="m7 9 5-5 5 5"></path><path d="M4 20h16"></path></svg>
            <div class="mt-[6px]">NID, trade license, agreement — ছবি বা PDF এখানে দিন</div>
            <input type="file" multiple x-ref="files" wire:model="files" accept=".pdf,image/*" class="hidden">
            <button type="button" @click="$refs.files.click()" class="mt-[10px] rounded-[8px] border border-[#c7d2fe] bg-white px-[14px] py-[8px] text-[13px] font-semibold text-[#4338ca]">Choose files</button>
            <div wire:loading wire:target="files" class="mt-[8px] text-[12px] text-[#6b7280]">Uploading…</div>
            @if ($files)
                <div class="mt-[10px] flex flex-wrap justify-center gap-[6px]">
                    @foreach ($files as $i => $f)
                        <span class="flex items-center gap-[6px] rounded-[6px] bg-[#eef2ff] px-[9px] py-[3px] text-[12px] text-[#3730a3]">{{ $f->getClientOriginalName() }}
                            <button type="button" wire:click="removeFile({{ $i }})" aria-label="Remove" class="text-[#6b7280]">×</button></span>
                    @endforeach
                </div>
            @endif
            @foreach ($errors->get('files.*') as $messages)
                <div class="mt-[6px] text-[12px] text-[#b91c1c]">{{ $messages[0] }}</div>
            @endforeach
        </div>
    </section>

    {{-- Tags & notes --}}
    <section class="{{ $section }}">
        <h2 class="m-0 text-[15px] font-bold">Tags &amp; notes</h2>
        <div class="flex flex-col gap-[6px]"><label for="t-tags" class="{{ $lbl }}">Tags</label><input id="t-tags" type="text" wire:model="tags" placeholder="Wholesale, Mirpur, VIP…" class="{{ $input }}"></div>
        <div class="flex flex-col gap-[6px]"><label for="t-notes" class="{{ $lbl }}">Notes</label><textarea id="t-notes" rows="3" wire:model="notes" placeholder="যেকোনো দরকারি তথ্য" class="{{ $input }} resize-y"></textarea></div>
        <label class="flex items-center gap-[8px] text-[13px]"><input type="checkbox" wire:model="active" class="rounded text-[#4f46e5]"> Active profile</label>
    </section>

    <div class="flex flex-wrap justify-end gap-[10px] pt-[4px]">
        <a href="{{ $profile ? route('accounts.profiles.show', $profile) : route('accounts.profiles.index') }}" wire:navigate class="rounded-[9px] border border-[#e5e7eb] bg-white px-[18px] py-[11px] text-[14px] !text-[#374151]">Cancel</a>
        <button type="button" wire:click="save(true)" wire:loading.attr="disabled" class="rounded-[9px] border border-[#c7d2fe] bg-white px-[18px] py-[11px] text-[14px] font-semibold text-[#3730a3]">Save &amp; add another</button>
        <button type="submit" wire:loading.attr="disabled" class="rounded-[9px] border-0 bg-[#4f46e5] px-[22px] py-[11px] text-[14px] font-bold text-white">Save profile</button>
    </div>
</form>
