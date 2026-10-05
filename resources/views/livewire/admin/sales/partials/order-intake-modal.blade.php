{{-- AI Order intake — part of the bulkSheet Alpine component (bulk-order-create.blade.php). --}}
<div x-show="intake.open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     @keydown.escape.window="if (intake.open && !intake.busy) closeIntake()">
    <div class="w-full max-w-4xl max-h-[92vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
            <div class="w-9 h-9 rounded-xl bg-linear-to-br from-violet-500 to-indigo-600 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-gray-900">AI Order</h2>
                <p class="text-xs text-gray-400">Paste a customer message or chat, or add screenshots. The parser fills what it can; AI is used only when something is still missing.</p>
            </div>
            <button type="button" @click="closeIntake()" :disabled="intake.busy" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 disabled:opacity-40">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Step 1: input --}}
        <div x-show="!intake.result" class="px-6 py-4 overflow-y-auto space-y-4">
            <div>
                <textarea x-model="intake.text" x-ref="intakeText" rows="10" @paste="intakePaste($event)"
                    @keydown.ctrl.enter.prevent="!intake.busy && runIntake(intake.forceAi)" @keydown.meta.enter.prevent="!intake.busy && runIntake(intake.forceAi)"
                    placeholder="e.g.&#10;Name: Rahima Akter&#10;Phone: 01711-223344&#10;Address: House 12, Road 5, Dhanmondi, Dhaka&#10;SF-0156 x2, discount 100&#10;&#10;Several orders? Paste them all — one block per customer. Screenshots can be pasted here too (Ctrl+V)."
                    class="w-full text-sm rounded-xl border border-gray-300 px-3 py-2.5 font-mono leading-relaxed focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                <p class="mt-1 text-[11px] text-gray-400" x-text="`${intake.text.length.toLocaleString()} / 20,000 characters · Ctrl+Enter to read`"></p>
            </div>

            {{-- Files --}}
            <div class="rounded-xl border-2 border-dashed px-4 py-3 transition"
                :class="intake.dragging ? 'border-indigo-400 bg-indigo-50/60' : 'border-gray-200'"
                @dragover.prevent="intake.dragging = true" @dragleave.prevent="intake.dragging = false"
                @drop.prevent="intake.dragging = false; intakeAddFiles($event.dataTransfer.files)">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></svg>
                        Add screenshots / files
                        <input type="file" multiple class="hidden" :accept="[...config.intake.mimes, '.xlsx', '.csv'].join(',')" @change="intakeAddFiles($event.target.files); $event.target.value = ''">
                    </label>
                    <span class="text-[11px] text-gray-400" x-text="`Images, PDF or .txt — up to ${config.intake.maxFiles} files, ${Math.round(config.intake.maxFileKb / 102.4) / 10} MB each. Excel/CSV goes straight into the sheet.`"></span>
                </div>
                <div x-show="intake.files.length" class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(f, i) in intake.files" :key="i">
                        <div class="relative group/file">
                            <template x-if="f.preview"><img :src="f.preview" class="h-16 w-16 rounded-lg object-cover border border-gray-200"></template>
                            <template x-if="!f.preview">
                                <div class="h-16 w-28 rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-[10px] text-gray-500 break-all overflow-hidden" x-text="f.file.name"></div>
                            </template>
                            <button type="button" @click="intakeRemoveFile(i)" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-gray-800 text-white text-[10px] leading-5 text-center">✕</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <label class="inline-flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500">Source</span>
                    <select x-model="intake.source" class="text-sm rounded-lg border border-gray-300 px-3 py-1.5">
                        <template x-for="s in config.sources" :key="s.value"><option :value="s.value" x-text="s.label" :selected="intake.source === s.value"></option></template>
                    </select>
                </label>
                <label x-show="config.intake.aiAvailable" class="inline-flex items-center gap-2 cursor-pointer text-gray-600">
                    <input type="checkbox" x-model="intake.forceAi" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    Always use AI for this one
                </label>
                <span x-show="!config.intake.aiAvailable" class="text-xs text-gray-400">
                    AI fallback is off — the parser only.
                    <template x-if="config.intake.settingsUrl"><a :href="config.intake.settingsUrl" class="text-indigo-600 hover:underline">Set up AI</a></template>
                </span>
            </div>

            <p x-show="intake.error" x-cloak class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700" x-text="intake.error"></p>
        </div>

        {{-- Step 2: review — every order editable before it goes to the sheet --}}
        <template x-if="intake.result">
            <div class="px-6 py-4 overflow-y-auto space-y-3 bg-gray-50/60">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-semibold text-gray-900" x-text="`${intake.cards.length} order${intake.cards.length === 1 ? '' : 's'} found`"></span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                        :class="{
                            'bg-sky-50 text-sky-700': intake.result.resolution === 'parser',
                            'bg-violet-50 text-violet-700': intake.result.resolution === 'ai',
                            'bg-red-50 text-red-700': intake.result.resolution === 'ai_failed',
                            'bg-amber-50 text-amber-700': intake.result.resolution === 'ai_skipped',
                        }"
                        x-text="{ parser: 'Read without AI', ai: `AI${intake.result.ai.cached ? ' (cached)' : ''}${intake.result.ai.model ? ' · ' + intake.result.ai.model : ''}`, ai_failed: 'AI failed — parser result kept', ai_skipped: 'AI not used' }[intake.result.resolution]"></span>
                    <span class="text-xs text-gray-400">Fix anything below, then add to the sheet.</span>
                </div>
                <p x-show="intake.result.ai.error" class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                    <span x-text="intake.result.ai.error"></span>
                    <template x-if="config.intake.settingsUrl && !config.intake.aiAvailable"><a :href="config.intake.settingsUrl" class="ml-1 font-medium underline">Set up AI</a></template>
                </p>
                <template x-if="intake.result.previous">
                    <p class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                        This same message was already placed <span x-text="intake.result.previous.at"></span> as
                        <span class="font-semibold" x-text="intake.result.previous.order_ids.map((id) => '#' + id).join(', ')"></span> — check for a duplicate.
                    </p>
                </template>

                <template x-for="(card, ci) in intake.cards" :key="card.key">
                    <div class="rounded-xl border bg-white p-3.5 space-y-3 transition"
                        :class="!card.include ? 'opacity-50 border-gray-200' : (cardProblems(card).length ? 'border-amber-300' : 'border-emerald-300')">

                        {{-- Head: include + status --}}
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-sm font-semibold text-gray-800">
                                <input type="checkbox" x-model="card.include" class="rounded border-gray-300 text-indigo-600">
                                <span x-text="`Order ${ci + 1}`"></span>
                            </label>
                            <span x-show="card.draft.customer?.found" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium"
                                x-text="`Existing customer · ${card.draft.customer?.orders} orders`"></span>
                            <span x-show="card.draft.customer?.recent" class="px-1.5 py-0.5 rounded bg-red-50 text-red-600 text-[10px] font-medium"
                                x-text="`Ordered #${card.draft.customer?.recent?.id} ${card.draft.customer?.recent?.ago}`"></span>
                            <div class="flex-1"></div>
                            <template x-if="!cardProblems(card).length">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">✓ Ready</span>
                            </template>
                            <template x-if="cardProblems(card).length">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700"
                                    x-text="`Needs: ${cardProblems(card).join(', ')}`"></span>
                            </template>
                        </div>

                        {{-- Customer --}}
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="sm:col-span-4">
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Name</label>
                                <input type="text" x-model="card.cells.name" @input="cardEdit(card, 'name')"
                                    :placeholder="card.draft.customer?.found ? card.draft.customer.name : 'Customer name'"
                                    class="w-full text-sm rounded-lg border px-2.5 py-1.5" :class="cardProblems(card).includes('name') ? 'border-red-300 bg-red-50/40' : 'border-gray-300'">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Phone</label>
                                <input type="text" x-model="card.cells.phone" @input="cardEdit(card, 'phone')" inputmode="tel" placeholder="01XXXXXXXXX"
                                    class="w-full text-sm rounded-lg border px-2.5 py-1.5 font-mono" :class="cardProblems(card).includes('phone') ? 'border-red-300 bg-red-50/40' : 'border-gray-300'">
                            </div>
                            <div class="sm:col-span-5">
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Delivery zone</label>
                                <select x-model="card.cells.method" @change="cardEdit(card, 'area')" class="w-full text-sm rounded-lg border border-gray-300 px-2.5 py-1.5">
                                    <option value="">Default zone</option>
                                    <template x-for="m in config.methods" :key="m.id"><option :value="String(m.id)" x-text="m.label" :selected="card.cells.method === String(m.id)"></option></template>
                                </select>
                            </div>
                            <div class="sm:col-span-12">
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Address</label>
                                <input type="text" x-model="card.cells.address" @input="cardEdit(card, 'address')"
                                    :placeholder="card.draft.customer?.address ? 'Saved: ' + card.draft.customer.address : 'Full delivery address'"
                                    class="w-full text-sm rounded-lg border px-2.5 py-1.5" :class="cardProblems(card).includes('address') ? 'border-red-300 bg-red-50/40' : 'border-gray-300'">
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-medium text-gray-400 uppercase">Products</label>
                            <template x-for="(it, k) in card.items" :key="k">
                                <div class="rounded-lg border px-2.5 py-2" :class="it.productId ? 'border-gray-200' : 'border-red-200 bg-red-50/40'">
                                    {{-- Matched --}}
                                    <template x-if="it.productId && !it.picking">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <img :src="productById(it.productId)?.image || ''" x-show="productById(it.productId)?.image" class="h-9 w-9 rounded-md object-cover bg-gray-100">
                                            <div class="flex-1 min-w-36">
                                                <p class="text-sm font-medium text-gray-800 leading-tight" x-text="productById(it.productId)?.name"></p>
                                                <button type="button" @click="it.picking = true" class="text-[11px] text-indigo-600 hover:underline">Change</button>
                                            </div>
                                            <template x-if="productById(it.productId)?.variable">
                                                <select x-model="it.variantId" @change="cardEdit(card, 'products')"
                                                    class="text-sm rounded-lg border py-1 px-2" :class="!it.variantId ? 'border-red-300 text-red-600' : 'border-gray-300'">
                                                    <option value="">Variant…</option>
                                                    <template x-for="v in productById(it.productId).variants" :key="v.id">
                                                        <option :value="String(v.id)" x-text="`${v.label} (${fmtQty(v.stock)})`" :selected="String(it.variantId) === String(v.id)"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[11px] text-gray-400">Qty</span>
                                                <input type="number" min="1" step="1" x-model="it.qty" @input="cardEdit(card, 'products')" class="w-14 text-sm rounded-lg border-gray-300 py-1 text-right">
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[11px] text-gray-400">Price</span>
                                                <input type="number" min="0" x-model="it.price" @input="cardEdit(card, 'products')"
                                                    :placeholder="money(defaultPriceFor(it.productId, it.variantId))" class="w-20 text-sm rounded-lg border-gray-300 py-1 text-right">
                                            </div>
                                            <span class="w-16 text-right text-sm font-semibold text-gray-700 tabular-nums" x-text="money((+it.qty || 0) * itemPrice(it))"></span>
                                            <button type="button" @click="cardRemoveItem(card, k)" title="Remove" class="w-6 h-6 inline-flex items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-500">✕</button>
                                        </div>
                                    </template>

                                    {{-- Not matched / changing: suggestions + search --}}
                                    <template x-if="!it.productId || it.picking">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <p class="flex-1 text-xs" :class="it.productId ? 'text-gray-500' : 'text-red-600'">
                                                    <span x-show="!it.productId">Not matched:</span>
                                                    <span x-show="it.productId">Change product:</span>
                                                    <b x-text="it.text ? `“${it.text}”` : ''"></b>
                                                </p>
                                                <button type="button" x-show="it.productId" @click="it.picking = false" class="text-[11px] text-gray-500 hover:underline">Cancel</button>
                                                <button type="button" @click="cardRemoveItem(card, k)" title="Remove" class="w-6 h-6 inline-flex items-center justify-center rounded text-gray-400 hover:bg-red-50 hover:text-red-500">✕</button>
                                            </div>
                                            <div x-show="it.options.length" class="flex flex-wrap items-center gap-1">
                                                <span class="text-[11px] text-gray-400">Did you mean:</span>
                                                <template x-for="id in it.options" :key="id">
                                                    <button type="button" @click="cardPick(card, it, productById(id))"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-indigo-200 bg-white text-[11px] font-medium text-indigo-700 hover:bg-indigo-50">
                                                        <span x-text="productById(id)?.name"></span>
                                                        <span class="text-gray-400" x-text="money(productById(id)?.price)"></span>
                                                    </button>
                                                </template>
                                            </div>
                                            <div class="relative">
                                                <input type="text" x-model="it.search" placeholder="Search product by name, code or SKU…"
                                                    @keydown.enter.prevent="searchProducts(it.search)[0] && cardPick(card, it, searchProducts(it.search)[0])"
                                                    class="w-full text-sm rounded-lg border border-gray-300 px-2.5 py-1.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <div x-show="it.search.trim().length" class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg divide-y divide-gray-100">
                                                    <template x-for="p in searchProducts(it.search).slice(0, 8)" :key="p.id">
                                                        <button type="button" @click="cardPick(card, it, p)" class="w-full flex items-center gap-2 px-2.5 py-1.5 text-left hover:bg-gray-50">
                                                            <img :src="p.image || ''" x-show="p.image" class="h-7 w-7 rounded object-cover bg-gray-100">
                                                            <span class="flex-1 min-w-0">
                                                                <span class="block text-sm text-gray-800 truncate" x-text="p.name"></span>
                                                                <span class="block text-[10px] text-gray-400" x-text="`${p.code}${p.variable ? ' · ' + p.variants.length + ' variants' : ''}`"></span>
                                                            </span>
                                                            <span class="text-xs font-medium text-gray-600 tabular-nums" x-text="money(p.price)"></span>
                                                        </button>
                                                    </template>
                                                    <p x-show="!searchProducts(it.search).length" class="px-2.5 py-2 text-xs text-gray-400">No product found.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <button type="button" @click="cardAddItem(card)" class="text-xs font-medium text-indigo-600 hover:underline">+ Add product</button>
                        </div>

                        {{-- Money --}}
                        <div class="flex flex-wrap items-end gap-3 border-t border-gray-100 pt-2.5">
                            <div>
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Delivery</label>
                                <input type="text" x-model="card.cells.delivery" inputmode="decimal"
                                    :placeholder="card.quoting ? '…' : (card.quote !== null ? `${card.quote} auto` : 'auto')"
                                    class="w-24 text-sm rounded-lg border border-gray-300 px-2 py-1 text-right">
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Discount</label>
                                <input type="text" x-model="card.cells.discount" @input="cardEdit(card, 'discount')" inputmode="decimal" placeholder="0" class="w-24 text-sm rounded-lg border border-gray-300 px-2 py-1 text-right">
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-gray-400 uppercase mb-0.5">Advance</label>
                                <input type="text" x-model="card.cells.advance" @input="cardEdit(card, 'advance')" inputmode="decimal" placeholder="0" class="w-24 text-sm rounded-lg border border-gray-300 px-2 py-1 text-right">
                            </div>
                            <div class="flex-1"></div>
                            <div class="text-right">
                                <p class="text-[11px] text-gray-400 tabular-nums"
                                    x-text="`${money(cardSubtotal(card))} + ${cardDelivery(card) === null ? '?' : money(cardDelivery(card))}${amount(card.cells.discount) ? ' − ' + money(amount(card.cells.discount)) : ''}`"></p>
                                <p class="text-base font-bold text-gray-900 tabular-nums">
                                    <span x-text="money(cardTotal(card))"></span>
                                    <span x-show="amount(card.cells.advance)" class="text-xs font-medium text-red-500" x-text="` · COD ${money(Math.max(0, cardTotal(card) - (amount(card.cells.advance) ?? 0)))}`"></span>
                                </p>
                            </div>
                        </div>

                        {{-- What the reader noticed --}}
                        <ul x-show="cardIssues(card).length" class="space-y-0.5 text-[11px] leading-snug">
                            <template x-for="(is, k) in cardIssues(card)" :key="k">
                                <li :class="{ error: 'text-red-600', warning: 'text-amber-600', info: 'text-gray-500' }[is.level]" x-text="`• ${is.message}`"></li>
                            </template>
                        </ul>
                    </div>
                </template>
                <p x-show="!intake.cards.length" class="py-6 text-center text-sm text-gray-400">No order could be read from this input.</p>
            </div>
        </template>

        {{-- Footer --}}
        <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 border-t border-gray-100">
            <div class="text-xs text-gray-400" x-show="intake.busy">
                <svg class="inline h-3.5 w-3.5 animate-spin mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span x-text="intake.busyText"></span>
            </div>
            <div class="flex-1"></div>
            <template x-if="!intake.result">
                <div class="flex gap-2">
                    <button type="button" @click="closeIntake()" :disabled="intake.busy" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Cancel</button>
                    <button type="button" @click="runIntake(intake.forceAi)" :disabled="intake.busy || !catalogReady || (!intake.text.trim() && !intake.files.length)"
                        class="px-5 py-2 text-sm font-medium text-white bg-linear-to-r from-violet-600 to-indigo-600 rounded-lg hover:opacity-90 disabled:opacity-50">Read order</button>
                </div>
            </template>
            <template x-if="intake.result">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="intake.result = null" :disabled="intake.busy" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">← Edit input</button>
                    <button type="button" x-show="config.intake.aiAvailable" @click="runIntake(true)" :disabled="intake.busy"
                        class="px-4 py-2 text-sm font-medium text-violet-700 bg-violet-50 border border-violet-200 rounded-lg hover:bg-violet-100 disabled:opacity-50">Retry with AI</button>
                    <button type="button" @click="addIntakeRows()" :disabled="intake.busy || !intake.cards.filter((c) => c.include).length"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        x-text="`Add ${intake.cards.filter((c) => c.include).length} to sheet`"></button>
                </div>
            </template>
        </div>
    </div>
</div>
