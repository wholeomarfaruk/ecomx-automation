<div x-data="bulkSheet(@js($config))" x-init="$store.pageName = { name: 'Bulk Order', slug: 'sales-orders-bulk' }"
     @beforeunload.window="if (hasUnsaved()) { $event.preventDefault(); $event.returnValue = ''; }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.orders') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Bulk Order</h1>
                <p class="text-xs text-gray-400">Type or paste rows from Excel — products, customers and delivery charges sync automatically.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="openIntake()" :disabled="!catalogReady"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-linear-to-r from-violet-600 to-indigo-600 rounded-lg hover:opacity-90 transition shadow-sm disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
                AI Order
            </button>
            <button type="button" wire:click="downloadTemplate"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Template
            </button>
            <label class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition cursor-pointer"
                :class="uploading && 'opacity-60 pointer-events-none'">
                <svg x-show="!uploading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                <svg x-show="uploading" x-cloak class="h-4 w-4 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                Upload Excel
                <input type="file" accept=".xlsx,.csv" class="hidden" @change="uploadSheet($event.target.files?.[0]); $event.target.value = ''">
            </label>
            <button type="button" @click="openConfirm()" :disabled="submitting || !catalogReady"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                <span x-text="`Place ${calc.ready.length} Order${calc.ready.length === 1 ? '' : 's'}`"></span>
            </button>
        </div>
    </div>

    {{-- Draft restore --}}
    <div x-show="draft" x-cloak class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm">
        <span class="text-amber-800">You have an unsaved bulk order draft (<span x-text="draft?.rows?.length"></span> rows, saved <span x-text="draft?.savedAgo"></span>).</span>
        <div class="flex gap-2">
            <button type="button" @click="restoreDraft()" class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700">Restore</button>
            <button type="button" @click="discardDraft()" class="px-3 py-1.5 text-xs font-medium text-amber-700 bg-white border border-amber-200 rounded-lg hover:bg-amber-100">Discard</button>
        </div>
    </div>

    {{-- Placed this session --}}
    <div x-show="placed.length" x-cloak class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-medium text-emerald-800">
                <span x-text="placed.length"></span> order(s) placed — total <span x-text="money(placed.reduce((s, p) => s + p.total, 0))"></span>
            </p>
            <a :href="config.ordersUrl" wire:navigate class="text-xs font-medium text-emerald-700 hover:underline">Go to Orders →</a>
        </div>
        <div class="mt-2 flex flex-wrap gap-1.5">
            <template x-for="p in placed" :key="p.id">
                <a :href="p.url" target="_blank" class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-emerald-200 text-xs font-medium text-emerald-700 hover:bg-emerald-100" x-text="`#${p.id}`"></a>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-5">

        {{-- Settings --}}
        <div class="col-span-12 min-w-0">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-5 py-4">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Order Status</label>
                        <select x-model="settings.status" class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed (books stock)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Default Source</label>
                        <select x-model="settings.source" class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <template x-for="s in config.sources" :key="s.value"><option :value="s.value" x-text="s.label" :selected="settings.source === s.value"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Default Delivery Zone</label>
                        <select x-model="settings.methodId" class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">No zone (charge 0 / typed)</option>
                            <template x-for="m in config.methods" :key="m.id"><option :value="String(m.id)" x-text="m.label" :selected="settings.methodId === String(m.id)"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Advance Received In</label>
                        <template x-if="config.accountsOn">
                            <select x-model="settings.advanceAccountId" class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— Choose account —</option>
                                <template x-for="a in config.accounts" :key="a.id"><option :value="String(a.id)" x-text="a.label" :selected="settings.advanceAccountId === String(a.id)"></option></template>
                            </select>
                        </template>
                        <template x-if="!config.accountsOn">
                            <select x-model="settings.advanceMethod" class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="bkash">bKash</option><option value="nagad">Nagad</option><option value="rocket">Rocket</option><option value="bank">Bank</option><option value="cash">Cash</option>
                            </select>
                        </template>
                    </div>
                    <div class="xl:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Admin Note (all orders)</label>
                        <input x-model="settings.adminNote" type="text" placeholder="Optional — e.g. Facebook live 3 Oct"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-600">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="settings.merge" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Merge rows with the same phone into one order
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="settings.smartZone" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Pick delivery zone from the address (Dhaka → inside)
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="settings.removePlaced" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Remove rows once their order is placed
                    </label>
                </div>
            </div>
        </div>

        {{-- Sheet --}}
        <div class="col-span-12 min-w-0">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Toolbar --}}
                <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-gray-100">
                    <span x-show="!catalogReady" class="inline-flex items-center gap-2 text-xs text-gray-500">
                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Loading products…
                    </span>
                    <span x-show="catalogReady" x-cloak class="text-xs text-gray-400" x-text="`${catalog.length} products loaded · paste anywhere in the sheet (Ctrl+V)`"></span>
                    <div class="flex-1"></div>

                    <template x-if="selectedCount">
                        <div class="flex flex-wrap items-center gap-2 rounded-lg bg-indigo-50 px-2 py-1">
                            <span class="text-xs font-medium text-indigo-700" x-text="`${selectedCount} selected`"></span>
                            <select @change="applyToSelected('method', $event.target.value); $event.target.value = '__'" class="text-xs rounded-md border-gray-300 py-1">
                                <option value="__">Set zone…</option>
                                <option value="">Auto</option>
                                <template x-for="m in config.methods" :key="m.id"><option :value="String(m.id)" x-text="m.label"></option></template>
                            </select>
                            <select @change="applyToSelected('source', $event.target.value); $event.target.value = '__'" class="text-xs rounded-md border-gray-300 py-1">
                                <option value="__">Set source…</option>
                                <template x-for="s in config.sources" :key="s.value"><option :value="s.value" x-text="s.label"></option></template>
                            </select>
                            <input type="number" min="0" placeholder="Delivery" @keydown.enter="applyToSelected('delivery', $event.target.value); $event.target.value = ''" class="w-24 text-xs rounded-md border-gray-300 py-1" title="Type and press Enter">
                            <input type="number" min="0" placeholder="Discount" @keydown.enter="applyToSelected('discount', $event.target.value); $event.target.value = ''" class="w-24 text-xs rounded-md border-gray-300 py-1" title="Type and press Enter">
                            <button type="button" @click="removeSelected()" class="px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-100 rounded-md">Delete</button>
                        </div>
                    </template>

                    <button type="button" @click="addRows(10)" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">+ 10 rows</button>
                    <button type="button" @click="removeEmptyRows()" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Remove empty</button>
                    <button type="button" @click="clearAll()" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">Clear sheet</button>
                </div>

                {{-- Grid --}}
                {{-- Fixed column widths (table-fixed + colgroup): the sheet is wider than the
                     screen and scrolls sideways instead of squeezing cells; # and Phone stay pinned. --}}
                <div class="sheet-scroll overflow-auto max-h-[68vh] overscroll-x-contain">
                    <table class="table-fixed text-sm border-separate border-spacing-0" style="width: 2606px">
                        <colgroup>
                            <col style="width: 56px">  {{-- select + # --}}
                            <col style="width: 150px"> {{-- phone --}}
                            <col style="width: 170px"> {{-- customer --}}
                            <col style="width: 260px"> {{-- address --}}
                            <col style="width: 220px"> {{-- products --}}
                            <col style="width: 280px"> {{-- matched items --}}
                            <col style="width: 70px">  {{-- qty --}}
                            <col style="width: 90px">  {{-- price --}}
                            <col style="width: 160px"> {{-- zone --}}
                            <col style="width: 100px"> {{-- delivery --}}
                            <col style="width: 90px">  {{-- discount --}}
                            <col style="width: 90px">  {{-- advance --}}
                            <col style="width: 110px"> {{-- total --}}
                            <col style="width: 100px"> {{-- due --}}
                            <col style="width: 130px"> {{-- source --}}
                            <col style="width: 200px"> {{-- note --}}
                            <col style="width: 240px"> {{-- status --}}
                            <col style="width: 90px">  {{-- actions --}}
                        </colgroup>
                        <thead class="sticky top-0 z-20">
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">
                                <th class="sticky left-0 z-30 bg-gray-50 border-b border-gray-200 px-3 py-2.5">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)" class="rounded border-gray-300 text-indigo-600">
                                </th>
                                <th class="sticky z-30 bg-gray-50 border-b border-r border-gray-200 px-3 py-2.5 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.12)]" style="left: 56px">Phone <span class="text-red-400">*</span></th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Customer</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Address</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Products <span class="text-red-400">*</span></th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Matched Items</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Qty</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Price</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Zone</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Delivery</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Discount</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Advance</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Total</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5 text-right">Due (COD)</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Source</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Note</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5">Status</th>
                                <th class="bg-gray-50 border-b border-gray-200 px-3 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, r) in rows" :key="row.key">
                                <tr class="group align-top" :class="rowClass(row)" @input="if (row.result && !row.result.ok) row.result = null" @change="if (row.result && !row.result.ok) row.result = null">
                                    <td class="sticky left-0 z-10 border-b border-gray-100 px-3 py-2.5" :class="stickyBg(row)">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" x-model="row.selected" class="rounded border-gray-300 text-indigo-600">
                                            <span class="text-[11px] text-gray-400 tabular-nums" x-text="r + 1"></span>
                                        </div>
                                        <template x-if="row.meta">
                                            <span class="mt-1 inline-block px-1 rounded text-[9px] font-semibold leading-4 cursor-help"
                                                :class="row.meta.reviewed || row.meta.confidence >= 0.85 ? 'bg-emerald-50 text-emerald-700' : (row.meta.confidence >= 0.6 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')"
                                                :title="intakeTitle(row)"
                                                x-text="row.meta.reviewed ? '✓ Checked' : `${row.meta.via === 'ai' ? 'AI' : 'Auto'} ${Math.round(row.meta.confidence * 100)}%`"></span>
                                        </template>
                                    </td>

                                    {{-- Phone --}}
                                    <td class="sticky z-10 border-b border-r border-gray-100 px-1.5 py-1.5 shadow-[4px_0_6px_-4px_rgba(0,0,0,0.12)]" style="left: 56px" :class="stickyBg(row)">
                                        <input type="text" x-model="row.cells.phone" :data-cell="`${r}:0`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 0)" @paste="onPaste($event, r, 0)" inputmode="tel" placeholder="01XXXXXXXXX"
                                            class="cell" :class="info(row).phoneBad && 'text-red-600'">
                                        <template x-if="info(row).customer">
                                            <div class="px-1.5 mt-0.5 flex flex-wrap items-center gap-1 text-[10px]">
                                                <template x-if="info(row).customer.found">
                                                    <a :href="info(row).customer.url" target="_blank" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 hover:underline"
                                                        x-text="`Existing · ${info(row).customer.orders} order${info(row).customer.orders === 1 ? '' : 's'}`"></a>
                                                </template>
                                                <template x-if="!info(row).customer.found">
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600">New customer</span>
                                                </template>
                                                <template x-if="info(row).customer.found && info(row).customer.cancelled">
                                                    <span class="px-1.5 py-0.5 rounded bg-red-50 text-red-600" x-text="`${info(row).customer.cancelled} cancelled/returned`"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </td>

                                    {{-- Name --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.name" :data-cell="`${r}:1`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 1)" @paste="onPaste($event, r, 1)"
                                            :placeholder="info(row).customer?.found ? info(row).customer.name : 'Name'" class="cell">
                                    </td>

                                    {{-- Address --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.address" :data-cell="`${r}:2`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 2)" @paste="onPaste($event, r, 2)"
                                            :placeholder="info(row).customer?.found && info(row).customer.address ? info(row).customer.address : 'Full address'"
                                            :title="row.cells.address" class="cell">
                                    </td>

                                    {{-- Products (free text) --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.products" :data-cell="`${r}:3`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 3)" @paste="onPaste($event, r, 3)"
                                            placeholder="Code / SKU / name x qty, …" :title="row.cells.products" class="cell font-mono text-xs">
                                    </td>

                                    {{-- Matched items --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <button type="button" @click="openEditor(row)" :disabled="isPlaced(row) || !catalogReady"
                                            class="w-full min-h-9 text-left rounded-lg border border-dashed border-gray-200 px-2 py-1 hover:border-indigo-300 hover:bg-indigo-50/60 transition flex flex-wrap gap-1 items-center">
                                            <template x-for="(it, i) in info(row).tokens" :key="i">
                                                <span class="inline-flex items-center gap-1 max-w-full px-1.5 py-0.5 rounded text-[11px] border"
                                                    :class="it.ok ? (it.match === 'exact' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700') : 'bg-red-50 border-red-200 text-red-600'"
                                                    :title="it.ok ? `${it.product.name}${it.variant ? ' — ' + it.variant.label : ''} · ${money(it.price)} each${it.match !== 'exact' ? ' (matched by name — check it)' : ''}` : it.problem">
                                                    <span class="truncate max-w-44" x-text="it.ok ? `${it.product.name}${it.variant ? ' · ' + it.variant.label : ''}` : (it.product ? `${it.product.name}: ${it.problem}` : `✕ ${it.raw}`)"></span>
                                                    <span x-show="it.ok" class="font-semibold" x-text="`×${fmtQty(it.qty)}`"></span>
                                                </span>
                                            </template>
                                            <span x-show="!info(row).tokens.length && !isPlaced(row)" class="text-[11px] text-gray-300">+ pick products</span>
                                        </button>
                                    </td>

                                    {{-- Qty / Price (single-product rows) --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.qty" :data-cell="`${r}:4`" :readonly="isPlaced(row) || info(row).multi"
                                            @keydown="nav($event, r, 4)" @paste="onPaste($event, r, 4)" inputmode="decimal"
                                            :placeholder="info(row).multi ? 'multi' : '1'" class="cell text-right">
                                    </td>
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.price" :data-cell="`${r}:5`" :readonly="isPlaced(row) || info(row).multi"
                                            @keydown="nav($event, r, 5)" @paste="onPaste($event, r, 5)" inputmode="decimal"
                                            :placeholder="info(row).multi ? 'multi' : (info(row).tokens[0]?.ok ? String(info(row).tokens[0].defaultPrice) : 'auto')" class="cell text-right">
                                    </td>

                                    {{-- Zone --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <template x-if="info(row).isLead">
                                            <div>
                                                <select x-model="row.cells.method" :data-cell="`${r}:6`" :disabled="isPlaced(row)" @keydown="nav($event, r, 6)"
                                                    class="cell pr-6" :class="!row.cells.method && 'text-gray-500'">
                                                    <option value="" x-text="`Auto: ${methodLabel(info(row).methodId) || 'none'}`"></option>
                                                    <template x-for="m in config.methods" :key="m.id"><option :value="String(m.id)" x-text="m.label" :selected="row.cells.method === String(m.id)"></option></template>
                                                </select>
                                            </div>
                                        </template>
                                        <template x-if="!info(row).isLead">
                                            <span class="block px-2 py-1.5 text-[11px] text-gray-400" x-text="`↳ with row ${info(row).leadIndex + 1}`"></span>
                                        </template>
                                    </td>

                                    {{-- Delivery --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <template x-if="info(row).isLead">
                                            <input type="text" x-model="row.cells.delivery" :data-cell="`${r}:7`" :readonly="isPlaced(row)"
                                                @keydown="nav($event, r, 7)" @paste="onPaste($event, r, 7)" inputmode="decimal"
                                                :placeholder="info(row).quotePending ? '…' : (info(row).autoDelivery !== null ? `${info(row).autoDelivery} auto` : '0')"
                                                class="cell text-right">
                                        </template>
                                        <template x-if="!info(row).isLead"><span class="block px-2 py-1.5 text-[11px] text-gray-300 text-right">—</span></template>
                                    </td>

                                    {{-- Discount / Advance --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.discount" :data-cell="`${r}:8`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 8)" @paste="onPaste($event, r, 8)" inputmode="decimal" placeholder="0" class="cell text-right">
                                    </td>
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.advance" :data-cell="`${r}:9`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 9)" @paste="onPaste($event, r, 9)" inputmode="decimal" placeholder="0" class="cell text-right">
                                    </td>

                                    {{-- Totals --}}
                                    <td class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums">
                                        <template x-if="info(row).isLead && info(row).group.items.length">
                                            <div>
                                                <div class="font-semibold text-gray-800" x-text="money(info(row).group.total)"></div>
                                                <div class="text-[10px] text-gray-400" x-text="`${money(info(row).group.subtotal)} + ${money(info(row).group.delivery)}${info(row).group.discount ? ' − ' + money(info(row).group.discount) : ''}`"></div>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="border-b border-gray-100 px-3 py-2.5 text-right tabular-nums">
                                        <template x-if="info(row).isLead && info(row).group.items.length">
                                            <div class="font-semibold" :class="info(row).group.due > 0 ? 'text-red-500' : 'text-emerald-600'" x-text="money(info(row).group.due)"></div>
                                        </template>
                                    </td>

                                    {{-- Source --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <select x-model="row.cells.source" :data-cell="`${r}:10`" :disabled="isPlaced(row)" @keydown="nav($event, r, 10)" class="cell pr-6" :class="!row.cells.source && 'text-gray-500'">
                                            <option value="" x-text="`Default (${sourceLabel(settings.source)})`"></option>
                                            <template x-for="s in config.sources" :key="s.value"><option :value="s.value" x-text="s.label" :selected="row.cells.source === s.value"></option></template>
                                        </select>
                                    </td>

                                    {{-- Note --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <input type="text" x-model="row.cells.note" :data-cell="`${r}:11`" :readonly="isPlaced(row)"
                                            @keydown="nav($event, r, 11)" @paste="onPaste($event, r, 11)" placeholder="Delivery note" :title="row.cells.note" class="cell">
                                    </td>

                                    {{-- Status --}}
                                    <td class="border-b border-gray-100 px-3 py-2.5 text-[11px] leading-snug">
                                        <template x-if="row.result?.ok">
                                            <a :href="row.result.url" target="_blank" class="inline-flex items-center gap-1 font-semibold text-emerald-600 hover:underline">
                                                ✓ Placed <span x-text="`#${row.result.id}`"></span>
                                            </a>
                                        </template>
                                        <template x-if="!row.result?.ok">
                                            <div class="space-y-0.5">
                                                <template x-if="row.result && !row.result.ok"><p class="text-red-600 font-medium" x-text="`✕ ${row.result.error}`"></p></template>
                                                <template x-for="e in info(row).errors" :key="e"><p class="text-red-600" x-text="`• ${e}`"></p></template>
                                                <template x-for="w in info(row).warnings" :key="w"><p class="text-amber-600" x-text="`• ${w}`"></p></template>
                                                <template x-if="info(row).recent">
                                                    <p class="text-amber-600">• Ordered <a :href="info(row).recent.url" target="_blank" class="underline" x-text="`#${info(row).recent.id}`"></a> <span x-text="info(row).recent.ago"></span></p>
                                                </template>
                                                <template x-if="!info(row).empty && !info(row).errors.length && !info(row).warnings.length && !info(row).recent && !row.result">
                                                    <p class="text-emerald-600" x-text="info(row).isLead ? 'Ready' : `Merged into row ${info(row).leadIndex + 1}`"></p>
                                                </template>
                                            </div>
                                        </template>
                                    </td>

                                    {{-- Row actions --}}
                                    <td class="border-b border-gray-100 px-1.5 py-1.5">
                                        <div class="flex items-center gap-0.5 opacity-40 group-hover:opacity-100 transition">
                                            <template x-if="row.meta?.sourceText && config.intake.aiAvailable && !isPlaced(row)">
                                                <button type="button" @click="retryRowWithAi(row)" :disabled="row.retrying" title="Read this order again with AI"
                                                    class="w-6 h-6 inline-flex items-center justify-center rounded text-violet-500 hover:bg-violet-50 disabled:opacity-40">
                                                    <svg x-show="!row.retrying" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
                                                    <svg x-show="row.retrying" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                                </button>
                                            </template>
                                            <button type="button" @click="duplicateRow(r)" title="Duplicate row" class="w-6 h-6 inline-flex items-center justify-center rounded text-gray-500 hover:bg-gray-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                            </button>
                                            <button type="button" @click="removeRow(r)" title="Delete row" class="w-6 h-6 inline-flex items-center justify-center rounded text-gray-500 hover:bg-red-50 hover:text-red-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Summary --}}
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-3 border-t border-gray-100 bg-gray-50/60 text-sm">
                    <div><span class="text-gray-400">Orders</span> <span class="font-semibold text-gray-800" x-text="calc.ready.length"></span></div>
                    <div><span class="text-gray-400">Items</span> <span class="font-semibold text-gray-800" x-text="fmtQty(calc.totals.qty)"></span></div>
                    <div><span class="text-gray-400">Subtotal</span> <span class="font-semibold text-gray-800" x-text="money(calc.totals.subtotal)"></span></div>
                    <div><span class="text-gray-400">Delivery</span> <span class="font-semibold text-gray-800" x-text="money(calc.totals.delivery)"></span></div>
                    <div><span class="text-gray-400">Discount</span> <span class="font-semibold text-gray-800" x-text="money(calc.totals.discount)"></span></div>
                    <div><span class="text-gray-400">Grand Total</span> <span class="font-bold text-indigo-600" x-text="money(calc.totals.total)"></span></div>
                    <div><span class="text-gray-400">Advance</span> <span class="font-semibold text-emerald-600" x-text="money(calc.totals.advance)"></span></div>
                    <div><span class="text-gray-400">COD Due</span> <span class="font-semibold text-red-500" x-text="money(calc.totals.due)"></span></div>
                    <div class="flex-1"></div>
                    <div x-show="calc.errorGroups" class="text-xs font-medium text-red-600" x-text="`${calc.errorGroups} order(s) need fixing`"></div>
                    <div x-show="calc.warningGroups" class="text-xs font-medium text-amber-600" x-text="`${calc.warningGroups} with warnings`"></div>
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-400 leading-relaxed">
                <b>Products</b> accepts product code, variant SKU or name, with quantity and price per item — e.g.
                <code class="px-1 bg-gray-100 rounded">SF-0156 x2, SF-0150-RED-XL</code> or <code class="px-1 bg-gray-100 rounded">Zareen 4 Pcs Red x1 @2200</code>.
                Pasting with a header row (Phone, Name, Address, Product, Size, Qty, Price, Delivery, Discount, Advance, Note…) maps columns by name.
                Use ↑ ↓ / Enter to move between rows.
            </p>
        </div>
    </div>

    {{-- Items editor --}}
    <div x-show="editor.open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @keydown.escape.window="editor.open = false">
        <div class="w-full max-w-3xl max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="editor.open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">Order items</h2>
                    <p class="text-xs text-gray-400" x-text="editor.title"></p>
                </div>
                <button @click="editor.open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto space-y-4">
                {{-- Lines --}}
                <div class="space-y-2">
                    <template x-for="(it, i) in editor.items" :key="i">
                        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 p-2.5">
                            <img :src="productById(it.productId)?.image || ''" x-show="productById(it.productId)?.image" class="h-10 w-10 rounded-lg object-cover bg-gray-100">
                            <div class="flex-1 min-w-40">
                                <p class="text-sm font-medium text-gray-800" x-text="productById(it.productId)?.name"></p>
                                <p class="text-[11px] text-gray-400" x-text="productById(it.productId)?.code"></p>
                            </div>
                            <template x-if="productById(it.productId)?.variable">
                                <select x-model="it.variantId" @change="it.price = String(defaultPriceFor(it.productId, it.variantId))"
                                    class="text-sm rounded-lg border-gray-300 py-1.5" :class="!it.variantId && 'border-red-300 text-red-600'">
                                    <option value="">Choose variant…</option>
                                    <template x-for="v in productById(it.productId).variants" :key="v.id">
                                        <option :value="String(v.id)" x-text="`${v.label} (${fmtQty(v.stock)} in stock)`" :selected="String(it.variantId) === String(v.id)"></option>
                                    </template>
                                </select>
                            </template>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-gray-400">Qty</span>
                                <input type="number" min="1" step="1" x-model="it.qty" class="w-16 text-sm rounded-lg border-gray-300 py-1.5 text-right">
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-gray-400">Price</span>
                                <input type="number" min="0" step="0.01" x-model="it.price" class="w-24 text-sm rounded-lg border-gray-300 py-1.5 text-right">
                            </div>
                            <span class="w-20 text-right text-sm font-semibold text-gray-700 tabular-nums" x-text="money((+it.qty || 0) * (+it.price || 0))"></span>
                            <button type="button" @click="editor.items.splice(i, 1)" class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            <template x-if="it.note"><p class="w-full text-[11px] text-amber-600" x-text="it.note"></p></template>
                        </div>
                    </template>
                    <p x-show="!editor.items.length" class="text-sm text-gray-400 text-center py-3">No items yet — search below to add products.</p>
                </div>

                {{-- Unmatched text --}}
                <template x-if="editor.unmatched.length">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">
                        Not matched from the sheet: <span class="font-medium" x-text="editor.unmatched.join(', ')"></span> — pick the right product below; saving drops these.
                    </div>
                </template>

                {{-- Search & add --}}
                <div>
                    <input type="text" x-model="editor.search" x-ref="editorSearch" placeholder="Search products by name, code or SKU…"
                        class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <div class="mt-2 max-h-64 overflow-y-auto divide-y divide-gray-100 rounded-lg border border-gray-100" x-show="editor.search.trim().length">
                        <template x-for="p in searchProducts(editor.search)" :key="p.id">
                            <button type="button" @click="editorAdd(p)" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-gray-50">
                                <img :src="p.image || ''" x-show="p.image" class="h-8 w-8 rounded object-cover bg-gray-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 truncate" x-text="p.name"></p>
                                    <p class="text-[11px] text-gray-400" x-text="`${p.code}${p.variable ? ' · ' + p.variants.length + ' variants' : ''}`"></p>
                                </div>
                                <span class="text-xs text-gray-500" x-text="p.variable ? 'variants' : `${fmtQty(p.stock ?? 0)} in stock`"></span>
                                <span class="text-sm font-medium text-gray-700 tabular-nums" x-text="money(p.price)"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 px-6 py-4 border-t border-gray-100">
                <span class="text-sm text-gray-500">Subtotal <b class="text-gray-800" x-text="money(editor.items.reduce((s, it) => s + (+it.qty || 0) * (+it.price || 0), 0))"></b></span>
                <div class="flex gap-2">
                    <button type="button" @click="editor.open = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="button" @click="saveEditor()" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save items</button>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.admin.sales.partials.order-intake-modal')

    {{-- Confirm & progress --}}
    <div x-show="confirm.open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-6 py-5 space-y-3">
                <h2 class="text-base font-semibold text-gray-900" x-text="submitting ? 'Placing orders…' : 'Place bulk orders?'"></h2>
                <template x-if="!submitting">
                    <div class="space-y-2 text-sm text-gray-600">
                        <p><b class="text-gray-900" x-text="calc.ready.length"></b> order(s), grand total <b class="text-gray-900" x-text="money(calc.totals.total)"></b>, COD due <b class="text-gray-900" x-text="money(calc.totals.due)"></b>.</p>
                        <p>Status: <b x-text="settings.status === 'confirmed' ? 'Confirmed — stock will be booked' : 'Pending'"></b></p>
                        <p x-show="calc.newCustomers">New customers to create: <b x-text="calc.newCustomers"></b></p>
                        <p x-show="calc.errorGroups" class="text-red-600"><span x-text="calc.errorGroups"></span> order(s) with errors will be skipped.</p>
                        <p x-show="calc.recentGroups" class="text-amber-600"><span x-text="calc.recentGroups"></span> customer(s) already ordered in the last 24 hours — check for duplicates.</p>
                    </div>
                </template>
                <template x-if="submitting">
                    <div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-indigo-600 transition-all" :style="`width: ${progress.total ? Math.round(progress.done / progress.total * 100) : 0}%`"></div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500" x-text="`${progress.done} of ${progress.total} processed · ${progress.ok} placed · ${progress.failed} failed`"></p>
                    </div>
                </template>
            </div>
            <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100" x-show="!submitting">
                <button type="button" @click="confirm.open = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" @click="submitAll()" :disabled="!calc.ready.length" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">Place orders</button>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .cell { display: block; width: 100%; height: 2.25rem; border: 1px solid #eceef1; border-radius: .5rem; background: rgba(255,255,255,.85); padding: .4375rem .625rem; font-size: .8125rem; line-height: 1.25rem; color: #1f2937; text-overflow: ellipsis; white-space: nowrap; transition: border-color .12s, box-shadow .12s; }
        select.cell { padding-right: 1.75rem; }
        /* Always-visible scrollbars on the sheet (both axes). */
        .sheet-scroll { scrollbar-width: auto; scrollbar-color: #cbd5e1 #f1f5f9; }
        .sheet-scroll::-webkit-scrollbar { width: 12px; height: 12px; }
        .sheet-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .sheet-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; border: 3px solid #f1f5f9; }
        .sheet-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sheet-scroll::-webkit-scrollbar-corner { background: #f1f5f9; }
        .cell:hover { border-color: #d1d5db; }
        .cell:focus { outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 1px #6366f1; }
        .cell[readonly], .cell:disabled { color: #6b7280; cursor: default; }
        .cell::placeholder { color: #c4c8cf; }
    </style>
</div>

@script
<script>
    const PASTE_COLS = ['phone', 'name', 'address', 'products', 'qty', 'price', 'method', 'delivery', 'discount', 'advance', 'source', 'note'];

    // Header names (lowercased, letters/digits only) → sheet column. "variant" columns are
    // appended to the product text so "Product | Size" pastes resolve to the right variant.
    const HEADER_ALIASES = {
        phone: ['phone', 'mobile', 'number', 'contact', 'phoneno', 'mobileno', 'phonenumber', 'mobilenumber', 'customerphone', 'cell', 'ফোন', 'মোবাইল', 'নাম্বার', 'নম্বর'],
        name: ['name', 'customer', 'customername', 'fullname', 'receiver', 'recipient', 'নাম'],
        address: ['address', 'fulladdress', 'deliveryaddress', 'shippingaddress', 'location', 'ঠিকানা'],
        products: ['product', 'products', 'item', 'items', 'sku', 'code', 'productcode', 'productname', 'itemname', 'পণ্য', 'প্রোডাক্ট'],
        variant: ['variant', 'size', 'color', 'colour', 'option', 'সাইজ', 'কালার'],
        qty: ['qty', 'quantity', 'pcs', 'piece', 'pieces', 'পরিমাণ'],
        price: ['price', 'unitprice', 'rate', 'sellprice', 'দাম'],
        method: ['zone', 'deliveryzone', 'shippingzone', 'area', 'deliveryarea', 'region'],
        delivery: ['delivery', 'deliverycharge', 'deliveryfee', 'shipping', 'shippingcharge', 'shippingfee', 'dc', 'charge', 'ডেলিভারি', 'ডেলিভারিচার্জ'],
        discount: ['discount', 'less', 'ছাড়', 'ডিসকাউন্ট'],
        advance: ['advance', 'paid', 'advancepaid', 'prepaid', 'অগ্রিম', 'এডভান্স'],
        source: ['source', 'channel', 'platform', 'from'],
        note: ['note', 'notes', 'remarks', 'remark', 'comment', 'comments', 'instruction', 'নোট'],
    };

    const SOURCE_ALIASES = { fb: 'messenger', facebook: 'messenger', page: 'messenger', messenger: 'messenger', inbox: 'messenger', wa: 'whatsapp', whatsapp: 'whatsapp', web: 'website', website: 'website', site: 'website', admin: 'admin', phone: 'admin', call: 'admin', api: 'api' };

    const BN_DIGITS = { '০': '0', '১': '1', '২': '2', '৩': '3', '৪': '4', '৫': '5', '৬': '6', '৭': '7', '৮': '8', '৯': '9' };
    const asciiDigits = (s) => String(s ?? '').replace(/[০-৯]/g, (d) => BN_DIGITS[d]);
    const num = (v) => { const n = parseFloat(asciiDigits(v).replace(/[^\d.\-]/g, '')); return Number.isFinite(n) ? n : null; };
    const norm = (s) => asciiDigits(s).toLowerCase().replace(/\s+/g, ' ').trim();
    const words = (s) => norm(s).split(/[^\p{L}\p{N}]+/u).filter(Boolean);

    let keySeq = 0;
    const blankCells = () => ({ phone: '', name: '', address: '', products: '', qty: '', price: '', method: '', delivery: '', discount: '', advance: '', source: '', note: '' });
    // meta: rows that came from AI Order — { intakeId, via, confidence, issues, needsAi, sourceText }.
    const newRow = (cells = {}, meta = null) => ({ key: `r${Date.now().toString(36)}${(keySeq++).toString(36)}`, cells: { ...blankCells(), ...cells }, meta, selected: false, result: null, retrying: false });

    Alpine.data('bulkSheet', (config) => ({
        config,
        catalog: [],
        catalogReady: false,
        idx: { byId: new Map(), bySku: new Map(), byCode: new Map() },
        rows: Array.from({ length: 15 }, () => newRow()),
        settings: {
            status: 'pending', source: 'messenger', methodId: config.defaultMethodId ? String(config.defaultMethodId) : '',
            advanceAccountId: config.accounts.length === 1 ? String(config.accounts[0].id) : '', advanceMethod: 'bkash',
            adminNote: '', merge: true, smartZone: true, removePlaced: false,
        },
        customers: {},          // national phone → lookup result
        quotes: {},             // signature → amount
        pending: { customers: new Set(), quotes: new Set() },
        calc: { rows: {}, groups: [], ready: [], totals: {}, errorGroups: 0, warningGroups: 0, recentGroups: 0, newCustomers: 0 },
        parseCache: new Map(),
        editor: { open: false, rowKey: null, title: '', items: [], unmatched: [], search: '' },
        confirm: { open: false },
        submitting: false,
        uploading: false,
        progress: { done: 0, total: 0, ok: 0, failed: 0 },
        placed: [],
        draft: null,
        draftKey: `bulk-order-draft-${config.userId}`,
        intake: { open: false, text: '', files: [], source: 'messenger', forceAi: false, busy: false, busyText: '', error: '', result: null, cards: [], dragging: false },

        init() {
            this.loadDraftBanner();
            this.$wire.catalog().then((products) => {
                this.catalog = products;
                for (const p of products) {
                    this.idx.byId.set(p.id, p);
                    if (p.code) this.idx.byCode.set(norm(p.code), p);
                    for (const v of p.variants) {
                        if (v.sku) this.idx.bySku.set(norm(v.sku), { p, v });
                    }
                }
                this.catalogReady = true;
                this.parseCache.clear();
                this.recalc();
            });

            this.recalc();
            if (this.config.intake.open) this.$nextTick(() => this.openIntake());
            this.$watch('rows', () => { this.recalc(); this.saveDraft(); });
            this.$watch('settings', () => this.recalc());
        },

        // ---------- helpers ----------
        money(v) { return (Math.round((+v || 0) * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }); },
        fmtQty(v) { return String(Math.round((+v || 0) * 1000) / 1000); },
        productById(id) { return this.idx.byId.get(+id); },
        methodLabel(id) { return this.config.methods.find((m) => String(m.id) === String(id))?.label; },
        sourceLabel(v) { return this.config.sources.find((s) => s.value === v)?.label ?? v; },
        defaultPriceFor(productId, variantId) {
            const p = this.productById(productId);
            const v = p?.variants.find((x) => String(x.id) === String(variantId));
            return v ? v.price : (p?.price ?? 0);
        },
        info(row) { return this.calc.rows[row.key] ?? { tokens: [], errors: [], warnings: [], empty: true, isLead: true, group: { items: [] } }; },
        isPlaced(row) { return !!row.result?.ok; },
        isEmpty(row) { return Object.entries(row.cells).every(([k, v]) => String(v ?? '').trim() === ''); },
        rowClass(row) {
            if (row.result?.ok) return 'bg-emerald-50/60';
            const i = this.info(row);
            if (i.empty) return 'bg-white';
            if (row.result && !row.result.ok) return 'bg-red-50/50';
            if (i.errors.length) return 'bg-red-50/30';
            return 'bg-white hover:bg-gray-50/50';
        },
        /** Pinned (#, Phone) cells need an opaque background so scrolled cells don't show through. */
        stickyBg(row) {
            if (row.result?.ok) return 'bg-emerald-50';
            const i = this.info(row);
            if (!i.empty && (row.result || i.errors.length)) return 'bg-red-50';
            return 'bg-white group-hover:bg-gray-50';
        },
        nationalPhone(raw) {
            let d = asciiDigits(raw).replace(/\D/g, '');
            if (d.startsWith('00')) d = d.slice(2);
            if (d.startsWith(this.config.phoneCode) && d.length > 10) d = d.slice(this.config.phoneCode.length);
            return d.replace(/^0+/, '');
        },

        // ---------- product parsing & matching ----------
        splitTokens(text) {
            return asciiDigits(text).split(/[\n,;|]+|\s\+\s/).map((t) => t.trim()).filter(Boolean);
        },

        parseToken(raw) {
            let s = raw.replace(/\s+/g, ' ').trim();
            let qty = null, price = null, m;
            const exact = (t) => this.idx.bySku.has(norm(t)) || this.idx.byCode.has(norm(t));

            if ((m = s.match(/\s*@\s*(\d+(?:\.\d+)?)\s*(?:tk|taka|৳)?\s*$/i))) { price = +m[1]; s = s.slice(0, m.index).trim(); }
            // A whole code/SKU is never cut into a quantity (e.g. "BOX2"), and "x2" needs a space before it.
            if (exact(s)) return { ref: s, qty, price };
            if ((m = s.match(/(?:\s+[x×*]\s*|\s*[×*]\s*)(\d+(?:\.\d+)?)\s*$/i))) { qty = +m[1]; s = s.slice(0, m.index).trim(); }
            else if ((m = s.match(/\s+(\d+(?:\.\d+)?)\s*(?:pcs?|pieces?|টি|ta|nos?)\s*$/i))) { qty = +m[1]; s = s.slice(0, m.index).trim(); }
            else if ((m = s.match(/\s*\(\s*(\d+(?:\.\d+)?)\s*\)\s*$/))) { qty = +m[1]; s = s.slice(0, m.index).trim(); }
            else if ((m = s.match(/^(\d+(?:\.\d+)?)\s*(?:x|\*|×|pcs?)\s+/i))) { qty = +m[1]; s = s.slice(m[0].length).trim(); }

            return { ref: s, qty, price };
        },

        /** Best catalogue match for a product reference + the leftover words used to pick a variant. */
        resolveRef(ref) {
            const key = norm(ref);
            if (!key) return null;

            if (this.idx.bySku.has(key)) { const { p, v } = this.idx.bySku.get(key); return { p, v, hint: '', match: 'exact' }; }
            if (this.idx.byCode.has(key)) return { p: this.idx.byCode.get(key), v: null, hint: '', match: 'exact' };

            // A code/SKU written as one of the words, e.g. "SF-0156 Red XL" or "Zareen SF-0156".
            const parts = key.split(' ');
            for (let n = parts.length - 1; n >= 1; n--) {
                for (let start = 0; start + n <= parts.length; start++) {
                    const piece = parts.slice(start, start + n).join(' ');
                    const rest = [...parts.slice(0, start), ...parts.slice(start + n)].join(' ');
                    if (this.idx.bySku.has(piece)) { const { p, v } = this.idx.bySku.get(piece); return { p, v, hint: rest, match: 'exact' }; }
                    if (this.idx.byCode.has(piece)) return { p: this.idx.byCode.get(piece), v: null, hint: rest, match: 'exact' };
                }
            }

            // Name match: the product name inside the text (rest = variant hint), or strong word overlap.
            const refWords = words(key);
            let best = null, second = 0;
            for (const p of this.catalog) {
                const name = norm(p.name);
                let score = 0, hint = '';
                if (name === key) { score = 100; }
                else if (key.includes(name)) { score = 60 + name.length / 10; hint = key.replace(name, ' '); }
                else if (key.length >= 4 && name.includes(key)) { score = 50 + key.length / 10; }
                else {
                    const nameWords = words(name);
                    const common = nameWords.filter((w) => refWords.includes(w)).length;
                    const ratio = nameWords.length ? common / nameWords.length : 0;
                    if (common >= 2 && ratio >= 0.6) { score = 30 + ratio * 20; hint = refWords.filter((w) => !nameWords.includes(w)).join(' '); }
                }
                if (score > (best?.score ?? 0)) { second = best?.score ?? 0; best = { p, v: null, hint, score, match: score >= 100 ? 'exact' : 'name' }; }
                else if (score > second) { second = score; }
            }
            if (best && best.score - second < 1 && best.score < 100) return { ambiguous: true };

            return best;
        },

        pickVariant(p, hint) {
            if (!p.variable) return { v: null };
            const active = p.variants;
            if (!active.length) return { problem: 'no active variants' };
            const h = norm(hint);
            if (h) {
                const hw = words(h);
                const scored = active.map((v) => ({
                    // Short values (S, M, XL) only as whole words; longer ones ("light blue") anywhere.
                    v, score: v.values.filter((val) => hw.includes(val) || (val.length >= 3 && h.includes(val))).length + (v.sku && h.includes(norm(v.sku)) ? 5 : 0),
                })).sort((a, b) => b.score - a.score);
                if (scored[0].score > 0 && (scored.length === 1 || scored[0].score > scored[1].score)) return { v: scored[0].v };
            }
            if (active.length === 1) return { v: active[0] };
            return { problem: 'choose a variant' };
        },

        parseProducts(row) {
            const text = row.cells.products ?? '';
            const cacheKey = `${text}\u0001${row.cells.qty}\u0001${row.cells.price}`;
            if (this.parseCache.has(cacheKey)) return this.parseCache.get(cacheKey);

            const raws = this.splitTokens(text);
            const single = raws.length === 1;
            const tokens = raws.map((raw) => {
                const t = this.parseToken(raw);
                const res = this.resolveRef(t.ref);
                const qty = t.qty ?? (single ? num(row.cells.qty) : null) ?? 1;
                if (!res) return { raw, ok: false, problem: `"${t.ref}" not found`, qty };
                if (res.ambiguous) return { raw, ok: false, problem: `"${t.ref}" matches several products`, qty };

                let v = res.v, problem = null;
                if (!v && res.p.variable) ({ v, problem } = this.pickVariant(res.p, res.hint));
                const defaultPrice = v ? v.price : res.p.price;
                const price = t.price ?? (single ? num(row.cells.price) : null) ?? defaultPrice;

                return { raw, ok: !problem && qty > 0, problem: problem ?? (qty > 0 ? null : 'quantity must be more than 0'), product: res.p, variant: v ?? null, qty, price, defaultPrice, match: res.match };
            });

            const out = { tokens, multi: raws.length > 1 };
            if (this.catalogReady) this.parseCache.set(cacheKey, out);
            return out;
        },

        smartMethod(address) {
            if (!this.settings.smartZone || !address) return null;
            const a = norm(address);
            const dhaka = /dhaka|ঢাকা/.test(a);
            const inside = this.config.methods.find((m) => /inside|ভিতরে|within/.test(m.zone) && /dhaka|ঢাকা/.test(m.zone))
                ?? this.config.methods.find((m) => /inside/.test(m.zone));
            const outside = this.config.methods.find((m) => /outside|বাইরে/.test(m.zone));
            if (!inside || !outside) return null;
            return dhaka ? String(inside.id) : String(outside.id);
        },

        // ---------- the whole-sheet calculation ----------
        recalc() {
            const rowsInfo = {};
            const groupsByKey = new Map();
            const demand = new Map();

            this.rows.forEach((row, index) => {
                const empty = this.isEmpty(row);
                const national = this.nationalPhone(row.cells.phone);
                const parsed = empty ? { tokens: [], multi: false } : this.parseProducts(row);
                const info = { index, empty, national, tokens: parsed.tokens, multi: parsed.multi, errors: [], warnings: [], customer: this.customers[national] ?? null };
                info.phoneBad = !empty && national.length > 0 && national.length < 6;
                info.recent = info.customer?.found ? info.customer.recent : null;
                rowsInfo[row.key] = info;

                if (empty) return;
                const gKey = this.settings.merge && national.length >= 6 && !row.result?.ok ? `p:${national}` : `r:${row.key}`;
                if (!groupsByKey.has(gKey)) groupsByKey.set(gKey, { key: gKey, rows: [] });
                groupsByKey.get(gKey).rows.push(row);
            });

            const groups = [];
            for (const g of groupsByKey.values()) {
                const lead = g.rows[0];
                const leadInfo = rowsInfo[lead.key];
                const firstOf = (k) => g.rows.map((r) => String(r.cells[k] ?? '').trim()).find((v) => v !== '') ?? '';

                g.lead = lead;
                g.placed = g.rows.every((r) => r.result?.ok);
                g.items = g.rows.flatMap((r) => rowsInfo[r.key].tokens.filter((t) => t.ok));
                g.subtotal = g.items.reduce((s, t) => s + t.qty * t.price, 0);
                g.qty = g.items.reduce((s, t) => s + t.qty, 0);
                g.name = firstOf('name');
                g.address = firstOf('address');
                g.note = g.rows.map((r) => String(r.cells.note ?? '').trim()).filter(Boolean).join(' | ');
                g.source = lead.cells.source || this.settings.source;
                g.methodId = lead.cells.method || this.smartMethod(g.address || leadInfo.customer?.address) || this.settings.methodId || '';
                g.manual = String(lead.cells.delivery ?? '').trim() !== '';
                g.discount = g.rows.reduce((s, r) => s + (num(r.cells.discount) ?? 0), 0);
                g.advance = g.rows.reduce((s, r) => s + (num(r.cells.advance) ?? 0), 0);

                g.signature = g.methodId && g.items.length
                    ? `${g.methodId}|${g.items.map((t) => `${t.product.id}:${t.qty}`).join(',')}|${g.subtotal}` : null;
                g.autoDelivery = g.signature && this.quotes[g.signature] !== undefined ? this.quotes[g.signature] : null;
                // A typed delivery charge needs no quote — queueQuotes() never asks for one,
                // so waiting on it would keep the order from ever being ready.
                g.quotePending = !g.manual && !!g.signature && g.autoDelivery === null;
                g.delivery = g.manual ? (num(lead.cells.delivery) ?? 0) : (g.autoDelivery ?? 0);
                g.total = Math.max(0, g.subtotal + g.delivery - g.discount);
                g.due = Math.max(0, g.total - g.advance);

                // Validation (errors block the order; warnings don't).
                const errors = [], warnings = [];
                const customer = leadInfo.customer;
                if (!leadInfo.national) errors.push('Phone is required');
                else if (leadInfo.phoneBad) errors.push('Phone number is not valid');
                else if (this.config.phoneCode === '880' && !/^1[3-9]\d{8}$/.test(leadInfo.national)) warnings.push('Phone does not look like a BD mobile number');

                for (const r of g.rows) {
                    for (const m of r.meta?.issues ?? []) warnings.push(m);
                    for (const t of rowsInfo[r.key].tokens) {
                        if (!t.ok) errors.push(t.product ? `${t.product.name}: ${t.problem}` : t.problem);
                        else if (t.match !== 'exact') warnings.push(`"${t.raw}" matched by name → ${t.product.name}${t.variant ? ' · ' + t.variant.label : ''}`);
                    }
                }
                if (!g.items.length && !errors.length) errors.push('Add at least one product');
                if (customer && !customer.found) {
                    if (!g.name) errors.push('Name is required for a new customer');
                    if (!g.address) errors.push('Address is required for a new customer');
                }
                if (customer?.found && !g.address && !customer.address) errors.push('Address is required');
                if (!customer && leadInfo.national.length >= 6) warnings.push('Checking customer…');
                if (g.discount > g.subtotal + g.delivery) errors.push('Discount is larger than the total');
                if (g.advance > g.total) errors.push('Advance is larger than the total');
                if (g.advance > 0 && this.config.accountsOn && !this.settings.advanceAccountId) errors.push('Choose "Advance received in" above');
                if (g.quotePending) warnings.push('Calculating delivery…');

                g.errors = [...new Set(errors)];
                g.warnings = [...new Set(warnings)];
                groups.push(g);

                if (!g.placed) {
                    for (const t of g.items) {
                        const k = `${t.product.id}:${t.variant?.id ?? ''}`;
                        demand.set(k, (demand.get(k) ?? 0) + t.qty);
                    }
                }
            }

            // Stock: total demand across the whole sheet vs what's available now.
            for (const g of groups) {
                const seen = new Set();
                for (const t of g.items) {
                    const k = `${t.product.id}:${t.variant?.id ?? ''}`;
                    const stock = t.variant ? t.variant.stock : t.product.stock;
                    if (seen.has(k) || stock === null || stock === undefined) continue;
                    seen.add(k);
                    if ((demand.get(k) ?? 0) > stock) {
                        g.warnings.push(`${t.product.name}${t.variant ? ' · ' + t.variant.label : ''}: only ${this.fmtQty(stock)} in stock (sheet needs ${this.fmtQty(demand.get(k))})`);
                    }
                }

                g.rows.forEach((r, i) => {
                    const info = rowsInfo[r.key];
                    info.group = g;
                    info.isLead = i === 0;
                    info.leadIndex = rowsInfo[g.lead.key].index;
                    info.methodId = g.methodId;
                    info.autoDelivery = g.autoDelivery;
                    info.quotePending = g.quotePending;
                    if (i === 0) { info.errors = g.errors; info.warnings = g.warnings; }
                    else { info.errors = rowsInfo[r.key].tokens.filter((t) => !t.ok).map((t) => t.problem); }
                });
            }

            const active = groups.filter((g) => !g.placed);
            const ready = active.filter((g) => !g.errors.length && g.items.length && !g.quotePending && this.customers[rowsInfo[g.lead.key].national]);

            this.calc = {
                rows: rowsInfo,
                groups,
                ready,
                errorGroups: active.filter((g) => g.errors.length).length,
                warningGroups: active.filter((g) => !g.errors.length && g.warnings.length).length,
                recentGroups: ready.filter((g) => rowsInfo[g.lead.key].recent).length,
                newCustomers: new Set(ready.filter((g) => !this.customers[rowsInfo[g.lead.key].national]?.found).map((g) => rowsInfo[g.lead.key].national)).size,
                totals: ready.reduce((t, g) => ({
                    qty: t.qty + g.qty, subtotal: t.subtotal + g.subtotal, delivery: t.delivery + g.delivery,
                    discount: t.discount + g.discount, total: t.total + g.total, advance: t.advance + g.advance, due: t.due + g.due,
                }), { qty: 0, subtotal: 0, delivery: 0, discount: 0, total: 0, advance: 0, due: 0 }),
            };

            this.queueLookups(rowsInfo);
            this.queueQuotes(groups);
        },

        // ---------- server sync (debounced, batched) ----------
        queueLookups(rowsInfo) {
            const missing = [...new Set(Object.values(rowsInfo).map((i) => i.national))]
                .filter((n) => n.length >= 6 && !(n in this.customers) && !this.pending.customers.has(n));
            if (!missing.length) return;
            missing.forEach((n) => this.pending.customers.add(n));
            clearTimeout(this._lookupTimer);
            this._lookupTimer = setTimeout(() => {
                const batch = [...this.pending.customers].filter((n) => !(n in this.customers));
                this.pending.customers.clear();
                if (!batch.length) return;
                this.$wire.lookupCustomers(batch).then((res) => {
                    const next = { ...this.customers };
                    batch.forEach((n) => { next[n] = res[n] ?? { found: false, national: n }; });
                    this.customers = next;
                    this.recalc();
                }).catch(() => {});
            }, 350);
        },

        queueQuotes(groups) {
            const needed = groups.filter((g) => g.signature && !g.manual && this.quotes[g.signature] === undefined && !this.pending.quotes.has(g.signature));
            if (!needed.length) return;
            const requests = needed.map((g) => ({
                key: g.signature, method_id: +g.methodId, subtotal: g.subtotal,
                items: g.items.map((t) => ({ product_id: t.product.id, quantity: t.qty })),
            }));
            needed.forEach((g) => this.pending.quotes.add(g.signature));
            clearTimeout(this._quoteTimer);
            const queued = (this._quoteQueue = [...(this._quoteQueue ?? []), ...requests]);
            this._quoteTimer = setTimeout(() => {
                this._quoteQueue = [];
                this.$wire.quoteShipping(queued).then((res) => {
                    const next = { ...this.quotes };
                    queued.forEach((q) => { next[q.key] = res[q.key] ?? 0; this.pending.quotes.delete(q.key); });
                    this.quotes = next;
                    this.recalc();
                }).catch(() => {
                    // Don't leave rows stuck on "Calculating delivery…" — fall back to 0 and say so.
                    const next = { ...this.quotes };
                    queued.forEach((q) => { next[q.key] = 0; this.pending.quotes.delete(q.key); });
                    this.quotes = next;
                    this.notify('Could not calculate delivery — type the delivery charge instead', 'warning');
                    this.recalc();
                });
            }, 400);
        },

        // ---------- keyboard & paste ----------
        focusCell(r, c) {
            // Resolve the sheet now: `$root` is looked up from the element that
            // triggered the call, which may be gone by the next tick (e.g. a
            // button inside a modal that just closed) — and an error thrown in
            // $nextTick stops Alpine's own queued DOM updates.
            const root = this.$root ?? document;
            this.$nextTick(() => {
                const el = root?.querySelector?.(`[data-cell="${r}:${c}"]`);
                if (el) { el.focus(); el.select?.(); }
            });
        },

        nav(e, r, c) {
            if (e.target.tagName === 'SELECT' && e.key.startsWith('Arrow')) return;
            if (e.key === 'ArrowDown' || (e.key === 'Enter' && !e.shiftKey)) {
                e.preventDefault();
                if (r + 1 >= this.rows.length) this.addRows(5);
                this.focusCell(r + 1, c);
            } else if (e.key === 'ArrowUp' || (e.key === 'Enter' && e.shiftKey)) {
                e.preventDefault();
                if (r > 0) this.focusCell(r - 1, c);
            }
        },

        /** Excel copies as TSV; cells with tabs/newlines come quoted. */
        parseTsv(text) {
            const out = [];
            let row = [], cell = '', quoted = false;
            text = text.replace(/\r\n?/g, '\n');
            for (let i = 0; i < text.length; i++) {
                const ch = text[i];
                if (quoted) {
                    if (ch === '"' && text[i + 1] === '"') { cell += '"'; i++; }
                    else if (ch === '"') quoted = false;
                    else cell += ch;
                } else if (ch === '"' && cell === '') quoted = true;
                else if (ch === '\t') { row.push(cell); cell = ''; }
                else if (ch === '\n') { row.push(cell); out.push(row); row = []; cell = ''; }
                else cell += ch;
            }
            if (cell !== '' || row.length) { row.push(cell); out.push(row); }
            return out.filter((r) => r.some((c) => c.trim() !== ''));
        },

        onPaste(e, r, c) {
            const text = e.clipboardData?.getData('text/plain') ?? '';
            if (!/[\t\n]/.test(text.trim())) return; // single value — normal paste
            e.preventDefault();
            this.importGrid(this.parseTsv(text), r, c);
        },

        headerMap(cells) {
            const map = {};
            let hits = 0;
            cells.forEach((cell, i) => {
                const k = norm(cell).replace(/[^\p{L}\p{N}]/gu, '');
                for (const [col, aliases] of Object.entries(HEADER_ALIASES)) {
                    if (aliases.includes(k)) { map[i] = col; hits++; break; }
                }
            });
            return hits >= 2 ? map : null;
        },

        importGrid(grid, startRow, startCol) {
            if (!grid.length) return;
            const header = this.headerMap(grid[0]);
            const body = header ? grid.slice(1) : grid;
            if (header) startCol = 0;

            body.forEach((cells, i) => {
                const r = startRow + i;
                while (this.rows.length <= r) this.rows.push(newRow());
                const row = this.rows[r];
                if (row.result?.ok) return;

                const values = {};
                let variantText = '';
                cells.forEach((raw, j) => {
                    const v = String(raw ?? '').trim();
                    const col = header ? header[j] : PASTE_COLS[startCol + j];
                    if (!col) return;
                    if (col === 'variant') { variantText = [variantText, v].filter(Boolean).join(' '); return; }
                    values[col] = values[col] ? `${values[col]} ${v}` : v;
                });
                if (variantText) values.products = [values.products ?? row.cells.products, variantText].filter(Boolean).join(' ');

                for (const [col, v] of Object.entries(values)) row.cells[col] = this.coerce(col, v);
            });

            this.rows.push(...Array.from({ length: Math.max(0, startRow + body.length + 3 - this.rows.length) }, () => newRow()));
            this.notify(`${body.length} row(s) pasted${header ? ' — columns matched by header' : ''}`);
        },

        coerce(col, v) {
            if (col === 'source') {
                const k = norm(v).replace(/[^a-z]/g, '');
                return SOURCE_ALIASES[k] ?? (this.config.sources.find((s) => s.value === k)?.value ?? '');
            }
            if (col === 'method') {
                const k = norm(v);
                if (!k) return '';
                const m = this.config.methods.find((x) => norm(x.label) === k) ?? this.config.methods.find((x) => norm(x.label).includes(k) || k.includes(x.zone));
                return m ? String(m.id) : '';
            }
            if (['qty', 'price', 'delivery', 'discount', 'advance'].includes(col)) {
                const n = num(v);
                return n === null ? '' : String(n);
            }
            if (col === 'phone') return asciiDigits(v).replace(/[^\d+]/g, '');
            return v;
        },

        // ---------- rows ----------
        addRows(n) { this.rows.push(...Array.from({ length: n }, () => newRow())); },
        duplicateRow(r) { const copy = newRow({ ...this.rows[r].cells }); this.rows.splice(r + 1, 0, copy); },
        removeRow(r) { this.rows.splice(r, 1); if (!this.rows.length) this.addRows(5); },
        removeEmptyRows() { this.rows = this.rows.filter((row) => !this.isEmpty(row)); if (!this.rows.length) this.addRows(5); },
        clearAll() {
            Swal.fire({ title: 'Clear the whole sheet?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Clear' })
                .then((r) => { if (r.isConfirmed) { this.rows = Array.from({ length: 15 }, () => newRow()); this.clearDraft(); } });
        },
        get selectedCount() { return this.rows.filter((r) => r.selected).length; },
        get allSelected() { const live = this.rows.filter((r) => !this.isEmpty(r)); return live.length > 0 && live.every((r) => r.selected); },
        toggleAll(on) { this.rows.forEach((r) => { r.selected = on && !this.isEmpty(r); }); },
        applyToSelected(col, value) {
            if (value === '__') return;
            this.rows.filter((r) => r.selected && !r.result?.ok).forEach((r) => { r.cells[col] = this.coerce(col, value) || (col === 'method' ? value : ''); });
        },
        removeSelected() { this.rows = this.rows.filter((r) => !r.selected); if (!this.rows.length) this.addRows(5); },

        // ---------- items editor ----------
        openEditor(row) {
            const info = this.info(row);
            this.editor = {
                open: true, rowKey: row.key, search: '',
                title: [row.cells.phone, row.cells.name].filter(Boolean).join(' · ') || `Row ${info.index + 1}`,
                items: info.tokens.filter((t) => t.product).map((t) => ({
                    productId: t.product.id, variantId: t.variant ? String(t.variant.id) : '', qty: String(t.qty), price: String(t.price),
                    note: t.match !== 'exact' ? `Matched "${t.raw}" by name — check it's right` : '',
                })),
                unmatched: info.tokens.filter((t) => !t.product).map((t) => t.raw),
            };
            { const refs = this.$refs; this.$nextTick(() => refs?.editorSearch?.focus()); }
        },

        searchProducts(q) {
            const k = norm(q);
            if (!k) return [];
            const kw = words(k);
            return this.catalog.filter((p) => {
                const hay = norm(`${p.name} ${p.code} ${p.variants.map((v) => v.sku).join(' ')}`);
                return kw.every((w) => hay.includes(w));
            }).slice(0, 30);
        },

        editorAdd(p) {
            this.editor.items.push({ productId: p.id, variantId: p.variable && p.variants.length === 1 ? String(p.variants[0].id) : '', qty: '1', price: String(p.variable && p.variants.length === 1 ? p.variants[0].price : p.price), note: '' });
            this.editor.search = '';
            { const refs = this.$refs; this.$nextTick(() => refs?.editorSearch?.focus()); }
        },

        saveEditor() {
            const row = this.rows.find((r) => r.key === this.editor.rowKey);
            if (!row) { this.editor.open = false; return; }
            const tokens = this.editor.items.map((it) => {
                const p = this.productById(it.productId);
                const v = p?.variants.find((x) => String(x.id) === String(it.variantId));
                const ref = v ? (v.sku || `${p.code} ${v.label}`) : (p.code || p.name);
                const qty = +it.qty > 0 ? +it.qty : 1;
                const def = v ? v.price : p.price;
                const price = it.price !== '' && +it.price !== +def ? ` @${+it.price}` : '';
                return `${ref} x${qty}${price}`;
            });
            row.cells.products = tokens.join(', ');
            row.cells.qty = '';
            row.cells.price = '';
            this.editor.open = false;
        },

        // ---------- upload ----------
        uploadSheet(file) {
            if (!file) return;
            this.uploading = true;
            this.$wire.upload('sheetFile', file,
                () => this.$wire.readUpload().then((grid) => {
                    this.uploading = false;
                    const start = this.rows.findIndex((r) => this.isEmpty(r));
                    this.importGrid(grid, start < 0 ? this.rows.length : start, 0);
                }).catch(() => { this.uploading = false; this.notify('Could not read that file — use an .xlsx or .csv like the template', 'error'); }),
                () => { this.uploading = false; this.notify('Upload failed — use an .xlsx or .csv file under 5 MB', 'error'); });
        },

        // ---------- submit ----------
        openConfirm() {
            if (!this.calc.ready.length) {
                this.notify(this.calc.errorGroups ? 'Fix the highlighted rows first — nothing is ready to place yet' : 'Add some orders first', 'error');
                return;
            }
            this.confirm.open = true;
        },

        payloadFor(g) {
            const info = this.calc.rows[g.lead.key];
            return {
                key: g.lead.key,
                phone: info.national,
                name: g.name,
                address: g.address,
                items: g.items.map((t) => ({ product_id: t.product.id, variant_id: t.variant?.id ?? null, quantity: t.qty, unit_price: t.price })),
                method_id: g.methodId ? +g.methodId : null,
                delivery: g.delivery,
                delivery_manual: g.manual,
                discount: g.discount,
                advance: g.advance,
                source: g.source,
                note: g.note,
                intake_ids: [...new Set(g.rows.map((r) => r.meta?.intakeId).filter(Boolean))],
            };
        },

        async submitAll() {
            const groups = [...this.calc.ready];
            if (!groups.length) return;
            const d = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const batch = `B${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}-${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;
            const settings = { status: this.settings.status, batch, admin_note: this.settings.adminNote, advance_account_id: this.settings.advanceAccountId ? +this.settings.advanceAccountId : null, advance_method: this.settings.advanceMethod };

            this.submitting = true;
            this.progress = { done: 0, total: groups.length, ok: 0, failed: 0 };
            const byLead = new Map(groups.map((g) => [g.lead.key, g]));

            for (let i = 0; i < groups.length; i += 10) {
                const chunk = groups.slice(i, i + 10);
                let res;
                try {
                    res = await this.$wire.submit(chunk.map((g) => this.payloadFor(g)), settings);
                } catch (err) {
                    res = { results: Object.fromEntries(chunk.map((g) => [g.lead.key, { ok: false, error: 'Request failed — check your connection and retry' }])) };
                }
                for (const [key, result] of Object.entries(res?.results ?? {})) {
                    const g = byLead.get(key);
                    if (!g) continue;
                    g.rows.forEach((row) => { row.result = result; row.selected = false; });
                    if (result.ok) { this.progress.ok++; this.placed.push({ id: result.id, url: result.url, total: result.total }); }
                    else this.progress.failed++;
                }
                this.progress.done = Math.min(groups.length, i + chunk.length);
            }

            this.submitting = false;
            this.confirm.open = false;
            this.customers = {};   // refresh order counts / "ordered recently" for what's left
            if (this.settings.removePlaced) {
                this.rows = this.rows.filter((r) => !r.result?.ok);
                if (!this.rows.length) this.addRows(10);
            }
            this.notify(`${this.progress.ok} order(s) placed${this.progress.failed ? `, ${this.progress.failed} failed — see the Status column` : ''}`, this.progress.failed ? 'warning' : 'success');
        },

        // ---------- draft (this browser only) ----------
        hasUnsaved() { return this.rows.some((r) => !this.isEmpty(r) && !r.result?.ok); },
        saveDraft() {
            clearTimeout(this._draftTimer);
            this._draftTimer = setTimeout(() => {
                try {
                    const rows = this.rows.filter((r) => !this.isEmpty(r) && !r.result?.ok).map((r) => ({ cells: r.cells, meta: r.meta }));
                    if (rows.length) localStorage.setItem(this.draftKey, JSON.stringify({ rows, savedAt: Date.now() }));
                    else localStorage.removeItem(this.draftKey);
                } catch (e) {}
            }, 800);
        },
        loadDraftBanner() {
            try {
                const d = JSON.parse(localStorage.getItem(this.draftKey) || 'null');
                if (d?.rows?.length) {
                    const mins = Math.round((Date.now() - d.savedAt) / 60000);
                    this.draft = { ...d, savedAgo: mins < 1 ? 'just now' : mins < 60 ? `${mins} min ago` : `${Math.round(mins / 60)} h ago` };
                }
            } catch (e) {}
        },
        restoreDraft() {
            // Older drafts stored bare cells; newer ones { cells, meta }.
            this.rows = [...this.draft.rows.map((d) => d.cells ? newRow(d.cells, d.meta ?? null) : newRow(d)), ...Array.from({ length: 5 }, () => newRow())];
            this.draft = null;
        },
        discardDraft() { this.clearDraft(); this.draft = null; },
        clearDraft() { try { localStorage.removeItem(this.draftKey); } catch (e) {} },

        // ---------- AI Order intake ----------
        openIntake() {
            this.intake = { ...this.intake, open: true, error: '', result: null, cards: [], source: this.intake.result ? this.intake.source : this.settings.source };
            { const refs = this.$refs; this.$nextTick(() => refs?.intakeText?.focus()); }
        },
        closeIntake() {
            this.intake.files.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
            this.intake = { ...this.intake, open: false, text: '', files: [], forceAi: false, error: '', result: null, cards: [] };
        },
        intakeAddFiles(list) {
            for (const file of Array.from(list ?? [])) {
                const name = file.name.toLowerCase();
                // Spreadsheets aren't messages — they go through the sheet's own Excel import.
                if (/\.(xlsx|csv)$/.test(name)) { this.uploadSheet(file); this.closeIntake(); return; }
                if (!this.config.intake.mimes.includes(file.type)) { this.intake.error = `${file.name}: this file type isn't allowed`; continue; }
                if (file.size > this.config.intake.maxFileKb * 1024) { this.intake.error = `${file.name} is larger than ${Math.round(this.config.intake.maxFileKb / 102.4) / 10} MB`; continue; }
                if (this.intake.files.length >= this.config.intake.maxFiles) { this.intake.error = `At most ${this.config.intake.maxFiles} files`; break; }
                this.intake.files.push({ file, preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null });
            }
        },
        intakeRemoveFile(i) {
            const [f] = this.intake.files.splice(i, 1);
            if (f?.preview) URL.revokeObjectURL(f.preview);
        },
        intakePaste(e) {
            const files = [...(e.clipboardData?.files ?? [])].filter((f) => f.type.startsWith('image/'));
            if (files.length) { e.preventDefault(); this.intakeAddFiles(files); }
        },
        uploadIntakeFiles(files) {
            if (!files.length) return Promise.resolve();
            return new Promise((resolve, reject) => this.$wire.uploadMultiple('intakeFiles', files, resolve, () => reject(new Error('Upload failed'))));
        },
        async runIntake(forceAi) {
            const text = this.intake.text;
            // A paste from Excel/Sheets is columns, not a message — use the sheet's own column import.
            if (!this.intake.files.length && this.parseTsv(text).filter((r) => r.length >= 3).length >= 2) {
                const start = this.rows.findIndex((r) => this.isEmpty(r));
                this.importGrid(this.parseTsv(text), start < 0 ? this.rows.length : start, 0);
                this.closeIntake();
                return;
            }
            this.intake.busy = true;
            this.intake.error = '';
            try {
                this.intake.busyText = this.intake.files.length ? 'Uploading files…' : 'Reading…';
                await this.uploadIntakeFiles(this.intake.files.map((f) => Alpine.raw(f.file)));
                this.intake.busyText = forceAi || this.intake.files.length ? 'Reading with AI… this can take a few seconds' : 'Reading…';
                const res = await this.$wire.extractOrders(text, this.intake.source, !!forceAi);
                if (res?.error) this.intake.error = res.error;
                else { this.intake.cards = this.buildCards(res); this.intake.result = res; }
            } catch (err) {
                this.intake.error = 'Could not read the order — check your connection and try again.';
            } finally {
                this.intake.busy = false;
            }
        },
        // ---- review cards: each extracted order, editable before it goes to the sheet ----
        buildCards(res) {
            return (res.drafts ?? []).map((d, i) => ({
                key: `c${i}-${Date.now().toString(36)}`,
                include: true,
                cells: { ...res.rows[i].cells },
                meta: res.rows[i].meta,
                draft: d,
                edited: [],
                quote: d.amounts.delivery_source === 'quote' ? d.amounts.delivery : null,
                quoting: false,
                timer: null,
                items: d.items.map((it) => this.cardItem(it)),
            }));
        },
        cardItem(it = {}) {
            return {
                productId: it.product_id ?? null,
                variantId: it.variant_id ? String(it.variant_id) : '',
                qty: String(it.qty ?? 1),
                price: it.price_source === 'stated' ? String(it.unit_price) : '',
                text: it.text ?? '',
                options: (it.options ?? []).filter((id) => this.productById(id)),
                search: '',
                picking: !it.product_id,
            };
        },
        amount(v) { return num(v); },
        cardEdit(card, field) {
            if (!card.edited.includes(field)) card.edited.push(field);
            if (field === 'products' || field === 'area') this.requote(card);
        },
        cardPick(card, it, p) {
            if (!p) return;
            it.productId = p.id;
            it.variantId = p.variable && p.variants.length === 1 ? String(p.variants[0].id) : '';
            Object.assign(it, { price: '', search: '', picking: false, options: [] });
            this.cardEdit(card, 'products');
        },
        cardAddItem(card) { card.items.push(this.cardItem()); },
        cardRemoveItem(card, k) { card.items.splice(k, 1); this.cardEdit(card, 'products'); },
        itemPrice(it) {
            if (String(it.price).trim() !== '' && num(it.price) !== null) return num(it.price);
            return it.productId ? this.defaultPriceFor(it.productId, it.variantId) : 0;
        },
        cardSubtotal(card) { return card.items.filter((it) => it.productId).reduce((s, it) => s + (+it.qty || 0) * this.itemPrice(it), 0); },
        cardDelivery(card) {
            const typed = String(card.cells.delivery ?? '').trim();
            return typed !== '' ? (num(typed) ?? 0) : card.quote;
        },
        cardTotal(card) { return Math.max(0, this.cardSubtotal(card) + (this.cardDelivery(card) ?? 0) - (num(card.cells.discount) ?? 0)); },
        /** What still blocks this order — the same rules the sheet applies. */
        cardProblems(card) {
            const out = [];
            const national = this.nationalPhone(card.cells.phone);
            const existing = !!card.draft.customer?.found && card.draft.customer.national === national;
            if (!/^1[3-9]\d{8}$/.test(national)) out.push('phone');
            if (!existing && !String(card.cells.name ?? '').trim()) out.push('name');
            if (!existing && !String(card.cells.address ?? '').trim()) out.push('address');
            if (!card.items.length || card.items.some((it) => !it.productId)) out.push('product');
            if (card.items.some((it) => it.productId && this.productById(it.productId)?.variable && !it.variantId)) out.push('variant');
            return out;
        },
        /** The reader's notes, minus those about fields fixed here. */
        cardIssues(card) {
            return (card.draft.issues ?? []).filter((is) => !card.edited.includes(is.field)
                && !(is.field === 'products' && card.items.every((it) => it.productId)));
        },
        /** Delivery charge from Settings → Shipping for the card's zone and items (the sheet's own quote). */
        requote(card, delay = 350) {
            clearTimeout(card.timer);
            const methodId = +(card.cells.method || this.settings.methodId || 0);
            const items = card.items.filter((it) => it.productId && +it.qty > 0);
            if (!methodId || !items.length) { card.quote = null; return; }
            card.quoting = true;
            card.timer = setTimeout(() => {
                const key = card.key;
                this.$wire.quoteShipping([{ key, method_id: methodId, subtotal: this.cardSubtotal(card), items: items.map((it) => ({ product_id: it.productId, quantity: +it.qty })) }])
                    .then((res) => { card.quote = res?.[key] ?? 0; })
                    .catch(() => {})
                    .finally(() => { card.quoting = false; });
            }, delay);
        },
        /** A card → a sheet row, product cells written as codes/SKUs like the items editor does. */
        cardRow(card) {
            const tokens = card.items.map((it) => {
                const qty = +it.qty > 0 ? +it.qty : 1;
                if (!it.productId) return it.text ? `${it.text.replace(/[,;|]/g, ' ')} x${qty}` : null;
                const p = this.productById(it.productId);
                const v = p?.variants.find((x) => String(x.id) === String(it.variantId));
                const ref = v ? (v.sku || `${p.code} ${v.label}`) : (p.code || p.name);
                const def = v ? v.price : p.price;
                const price = String(it.price).trim() !== '' && +it.price !== +def ? ` @${+it.price}` : '';
                return `${ref} x${qty}${price}`;
            }).filter(Boolean);
            const fields = card.meta.issueFields ?? [];
            const keep = (i) => !card.edited.includes(fields[i]) && !(fields[i] === 'products' && card.items.every((it) => it.productId));
            return newRow({ ...card.cells, products: tokens.join(', '), qty: '', price: '' }, {
                ...card.meta,
                issues: card.meta.issues.filter((_, i) => keep(i)),
                issueFields: fields.filter((_, i) => keep(i)),
                reviewed: card.edited.length > 0,
            });
        },
        addIntakeRows() {
            const rows = (this.intake.cards ?? []).filter((c) => c.include).map((c) => this.cardRow(c));
            let at = this.rows.findIndex((r) => this.isEmpty(r) && !r.result);
            if (at < 0) at = this.rows.length;
            rows.forEach((row, i) => {
                if (this.rows[at + i] && this.isEmpty(this.rows[at + i])) this.rows.splice(at + i, 1, row);
                else this.rows.splice(at + i, 0, row);
            });
            this.notify(`${rows.length} order${rows.length === 1 ? '' : 's'} added to the sheet — review and place`);
            this.focusCell(at, 0);
            this.closeIntake();
        },
        intakeTitle(row) {
            const m = row.meta;
            return `${m.via === 'ai' ? 'Read by AI' : 'Read by the parser'} · ${Math.round(m.confidence * 100)}% sure` + (m.issues.length ? '\n• ' + m.issues.join('\n• ') : '');
        },
        async retryRowWithAi(row) {
            row.retrying = true;
            try {
                const res = await this.$wire.extractOrders(row.meta.sourceText, row.cells.source || this.settings.source, true);
                if (res?.error || !res?.rows?.length) {
                    this.notify(res?.error || 'AI could not read this order either', 'error');
                    return;
                }
                if (res.ai?.error) this.notify(res.ai.error, 'warning');
                const at = this.rows.indexOf(row);
                if (at < 0) return;
                this.rows.splice(at, 1, ...res.rows.map((r) => newRow(r.cells, r.meta)));
                this.notify('Row read again with AI');
            } catch (err) {
                this.notify('AI retry failed — check your connection', 'error');
            } finally {
                row.retrying = false;
            }
        },

        notify(message, icon = 'success') {
            if (window.Toast) Toast.fire({ icon, title: message });
        },
    }));
</script>
@endscript
