{{-- AI Order intake — part of the bulkSheet Alpine component (bulk-order-create.blade.php). --}}
<div x-show="intake.open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
     @keydown.escape.window="if (intake.open && !intake.busy) closeIntake()">
    <div class="w-full max-w-3xl max-h-[92vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden">

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
                    placeholder="e.g.&#10;Name: Rahima Akter&#10;Phone: 01711-223344&#10;Address: House 12, Road 5, Dhanmondi, Dhaka&#10;SF-0156 x2, discount 100&#10;&#10;Several orders? Paste them all — one block per customer. Screenshots can be pasted here too (Ctrl+V)."
                    class="w-full text-sm rounded-xl border border-gray-300 px-3 py-2.5 font-mono leading-relaxed focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                <p class="mt-1 text-[11px] text-gray-400" x-text="`${intake.text.length.toLocaleString()} / 20,000 characters`"></p>
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

        {{-- Step 2: what was found --}}
        <template x-if="intake.result">
            <div class="px-6 py-4 overflow-y-auto space-y-3">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-semibold text-gray-900" x-text="`${intake.result.drafts.length} order${intake.result.drafts.length === 1 ? '' : 's'} found`"></span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                        :class="{
                            'bg-sky-50 text-sky-700': intake.result.resolution === 'parser',
                            'bg-violet-50 text-violet-700': intake.result.resolution === 'ai',
                            'bg-red-50 text-red-700': intake.result.resolution === 'ai_failed',
                            'bg-amber-50 text-amber-700': intake.result.resolution === 'ai_skipped',
                        }"
                        x-text="{ parser: 'Parser only — no AI used', ai: `AI${intake.result.ai.cached ? ' (cached)' : ''}${intake.result.ai.model ? ' · ' + intake.result.ai.model : ''}`, ai_failed: 'AI failed — parser result kept', ai_skipped: 'AI not used' }[intake.result.resolution]"></span>
                </div>
                <p x-show="intake.result.ai.error" class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800" x-text="intake.result.ai.error"></p>
                <template x-if="intake.result.previous">
                    <p class="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
                        This same message was already placed <span x-text="intake.result.previous.at"></span> as
                        <span class="font-semibold" x-text="intake.result.previous.order_ids.map((id) => '#' + id).join(', ')"></span> — check for a duplicate.
                    </p>
                </template>

                <template x-for="(d, i) in intake.result.drafts" :key="i">
                    <div class="rounded-xl border p-3 space-y-2" :class="d.ready ? 'border-emerald-200' : 'border-amber-200'">
                        <div class="flex flex-wrap items-start gap-x-4 gap-y-1">
                            <div class="flex-1 min-w-48">
                                <p class="text-sm font-semibold text-gray-900">
                                    <span x-text="d.name.value || d.customer?.name || 'No name'"></span>
                                    <span class="font-normal text-gray-500" x-text="d.phone.display ? ' · ' + d.phone.display : ' · no phone'"></span>
                                    <span x-show="d.customer?.found" class="ml-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium" x-text="`Existing · ${d.customer?.orders} orders`"></span>
                                </p>
                                <p class="text-xs text-gray-500" x-text="d.address.value || (d.address.source === 'customer' ? 'Saved address: ' + (d.customer?.address ?? '') : 'No address')"></p>
                                <p class="text-xs text-gray-400" x-text="d.area.zone ? `Zone: ${d.area.zone}${d.area.area ? ' (' + d.area.area + ')' : ''}` : ''"></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="d.confidence >= 0.85 ? 'bg-emerald-50 text-emerald-700' : (d.confidence >= 0.6 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700')"
                                    x-text="`${Math.round(d.confidence * 100)}% sure · ${d.via === 'ai' ? 'AI' : 'parser'}`"></span>
                                <p class="mt-1 text-sm font-bold text-gray-900 tabular-nums" x-text="money(d.amounts.total)"></p>
                                <p class="text-[10px] text-gray-400 tabular-nums"
                                    x-text="`${money(d.amounts.subtotal)} + ${d.amounts.delivery === null ? '?' : money(d.amounts.delivery)}${d.amounts.discount ? ' − ' + money(d.amounts.discount) : ''}${d.amounts.advance ? ' · adv ' + money(d.amounts.advance) : ''}`"></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="(it, k) in d.items" :key="k">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] border"
                                    :class="it.product_id && it.confidence >= 0.6 ? (it.match === 'exact' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700') : 'bg-red-50 border-red-200 text-red-600'"
                                    x-text="it.product_id ? `${it.product_name}${it.variant_label ? ' · ' + it.variant_label : ''} ×${fmtQty(it.qty)} @${money(it.unit_price)}` : `✕ ${it.text}`"></span>
                            </template>
                        </div>
                        <ul x-show="d.issues.length" class="space-y-0.5 text-[11px] leading-snug">
                            <template x-for="(is, k) in d.issues" :key="k">
                                <li :class="{ error: 'text-red-600', warning: 'text-amber-600', info: 'text-gray-500' }[is.level]" x-text="`• ${is.message}`"></li>
                            </template>
                        </ul>
                    </div>
                </template>
                <p x-show="!intake.result.drafts.length" class="py-6 text-center text-sm text-gray-400">No order could be read from this input.</p>
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
                    <button type="button" @click="addIntakeRows()" :disabled="intake.busy || !intake.result.rows.length"
                        class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        x-text="`Add ${intake.result.rows.length} to sheet`"></button>
                </div>
            </template>
        </div>
    </div>
</div>
