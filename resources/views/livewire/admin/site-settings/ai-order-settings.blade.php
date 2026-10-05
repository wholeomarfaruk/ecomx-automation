@php
    $input = 'w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
    $label = 'block text-sm font-medium text-gray-700 mb-1.5';
    $hint = 'text-xs text-gray-400 mt-1.5';
@endphp
<div>
    <div class="px-6 py-5 space-y-6">

        @unless ($canManage)
            <p class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-800">You can view these settings; changing them needs the <code>site_settings.manage</code> permission.</p>
        @endunless

        {{-- How it works --}}
        <div class="rounded-xl bg-violet-50/60 border border-violet-100 px-4 py-3 text-xs text-violet-900 leading-relaxed">
            <b>Sales → Orders → AI Order</b> reads pasted messages, chats and screenshots into Bulk Order rows.
            The built-in parser always runs first (phones, names, addresses, products, discount, delivery). AI is called only when a
            required field is still missing or ambiguous, or for screenshots/PDFs. Product ids, prices and totals always come from your catalogue and
            shipping rules, never from the AI.
            <br><b>Fewer AI calls:</b> add the names customers actually write — Bangla or Banglish, e.g. <i>জারিন, zarin</i> — to a product's
            <b>Meta Keywords</b> (comma-separated); the parser matches those without AI. PDFs with real text are read without AI too.
        </div>

        {{-- Usage --}}
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Last 30 days</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ([
                    ['Extractions', number_format($stats['extractions'])],
                    ['Parser only (no AI)', number_format($stats['parser_only'])],
                    ['AI calls', number_format($stats['ai_calls']) . ($stats['ai_failed'] ? " · {$stats['ai_failed']} failed" : '')],
                    ['AI calls today', number_format($stats['ai_today'])],
                    ['Tokens', number_format($stats['tokens'])],
                    ['Cost (USD)', '$' . number_format($stats['cost'], 4)],
                    ['Avg AI latency', $stats['avg_latency'] ? number_format($stats['avg_latency'] / 1000, 1) . ' s' : '—'],
                    ['Orders placed', number_format($stats['orders_placed'])],
                ] as [$k, $v])
                    <div class="rounded-xl border border-gray-200 px-3 py-2.5">
                        <p class="text-[11px] text-gray-400">{{ $k }}</p>
                        <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $v }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Switch + key --}}
        <div class="border-t border-gray-100 pt-5 space-y-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" wire:model="enabled" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @disabled(! $canManage)>
                <span>
                    <span class="block text-sm font-medium text-gray-800">Use AI when the parser can't finish an order</span>
                    <span class="block text-xs text-gray-400">Off = parser only; unresolved fields are left for you to fix on the sheet.</span>
                </span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Provider</label>
                    <input type="text" value="OpenRouter" disabled class="{{ $input }} bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="{{ $label }}">OpenRouter API key</label>
                    <input type="password" wire:model="newApiKey" autocomplete="new-password"
                        placeholder="{{ $maskedKey ? 'Saved: ' . $maskedKey . ' — type to replace' : 'sk-or-v1-…' }}" class="{{ $input }} font-mono" @disabled(! $canManage)>
                    @error('newApiKey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    <p class="{{ $hint }}">
                        @switch($keySource)
                            @case('settings') Stored encrypted. @break
                            @case('env') Using <code class="bg-gray-100 px-1 rounded">OPENROUTER_API_KEY</code> from .env — saving a key here overrides it. @break
                            @default No key yet — create one at openrouter.ai/keys.
                        @endswitch
                    </p>
                    @if ($keySource === 'settings' && $canManage)
                        <label class="mt-1.5 inline-flex items-center gap-2 text-xs text-gray-500 cursor-pointer">
                            <input type="checkbox" wire:model="clearApiKey" class="rounded border-gray-300 text-red-600"> Remove the saved key
                        </label>
                    @endif
                </div>
            </div>
        </div>

        {{-- Models --}}
        <div class="border-t border-gray-100 pt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <datalist id="ai-order-models">
                <option value="openrouter/free">Free — OpenRouter picks a free model that supports images + JSON</option>
                <option value="google/gemini-2.5-flash">Paid — fast, good with screenshots</option>
                <option value="openai/gpt-4.1-mini">Paid</option>
                <option value="anthropic/claude-haiku-4.5">Paid</option>
            </datalist>
            <div>
                <label class="{{ $label }}">Primary model</label>
                <input type="text" wire:model="model" list="ai-order-models" class="{{ $input }} font-mono" @disabled(! $canManage)>
                @error('model') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="{{ $hint }}"><code class="bg-gray-100 px-1 rounded">openrouter/free</code> costs nothing but varies in quality; pick a paid model with image input for production.</p>
            </div>
            <div>
                <label class="{{ $label }}">Fallback model</label>
                <input type="text" wire:model="fallbackModel" list="ai-order-models" placeholder="Optional" class="{{ $input }} font-mono" @disabled(! $canManage)>
                @error('fallbackModel') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="{{ $hint }}">Tried by OpenRouter when the primary model is down or rate-limited.</p>
            </div>
            <div>
                <label class="{{ $label }}">Temperature</label>
                <input type="number" step="0.1" min="0" max="2" wire:model="temperature" class="{{ $input }}" @disabled(! $canManage)>
                @error('temperature') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="{{ $hint }}">0 = most literal (recommended for extraction).</p>
            </div>
            <div>
                <label class="{{ $label }}">Max reply tokens</label>
                <input type="number" min="500" max="16000" wire:model="maxTokens" class="{{ $input }}" @disabled(! $canManage)>
                @error('maxTokens') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Limits --}}
        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Limits</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach ([
                    ['timeout', 'Timeout per try (seconds)', '25 recommended — a slow free model is retried on another within the ~60 s a request may run'],
                    ['dailyLimit', 'AI calls per day', '0 = no limit'],
                    ['perMinute', 'AI calls per admin per minute', null],
                    ['maxFiles', 'Files per extraction', '1–10'],
                    ['maxFileKb', 'Max file size (KB)', null],
                    ['maxCandidates', 'Products shown to AI', 'Closest matches only, never the whole catalogue'],
                ] as [$field, $text, $help])
                    <div>
                        <label class="{{ $label }}">{{ $text }}</label>
                        <input type="number" wire:model="{{ $field }}" class="{{ $input }}" @disabled(! $canManage)>
                        @error($field) <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        @if ($help) <p class="{{ $hint }}">{{ $help }}</p> @endif
                    </div>
                @endforeach
            </div>

            <label class="{{ $label }} mt-4">Allowed file types</label>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach (\App\Livewire\Admin\SiteSettings\AiOrderSettings::MIME_OPTIONS as $mime => $name)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" wire:model="allowedMimes" value="{{ $mime }}" class="rounded border-gray-300 text-indigo-600" @disabled(! $canManage)> {{ $name }}
                    </label>
                @endforeach
            </div>
            @error('allowedMimes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Prompt --}}
        <div class="border-t border-gray-100 pt-5">
            <label class="{{ $label }}">Shop notes for the AI <span class="font-normal text-gray-400">(optional)</span></label>
            <textarea wire:model="extraPrompt" rows="3" placeholder="e.g. &quot;Free size&quot; means the Free Size variant. Customers often write product codes without the SF- prefix."
                class="{{ $input }}" @disabled(! $canManage)></textarea>
            @error('extraPrompt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            <p class="{{ $hint }}">Added to the built-in instructions. The AI is always told to report only what the message says.</p>
        </div>

        {{-- Test --}}
        @if ($canManage)
            <div class="border-t border-gray-100 pt-5 space-y-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Test</p>
                <textarea wire:model="testText" rows="4" class="{{ $input }} font-mono text-xs"></textarea>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="testConnection" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50">Test connection</button>
                    <button type="button" wire:click="testExtraction" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-violet-700 bg-violet-50 border border-violet-200 rounded-lg hover:bg-violet-100 disabled:opacity-50">Test extraction (uses AI)</button>
                    <span wire:loading wire:target="testConnection,testExtraction" class="self-center text-xs text-gray-400">Working…</span>
                </div>
                <p class="{{ $hint }}">Test connection checks the key typed above, or the saved one. Test extraction uses the saved settings — save first.</p>
                @if ($testResult)
                    <div class="rounded-lg border px-3 py-2 text-sm {{ $testResult['ok'] ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700' }}">
                        <p class="font-medium">{{ $testResult['title'] }}</p>
                        <p class="text-xs mt-0.5">{{ $testResult['details'] }}</p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Recent --}}
        @if ($stats['recent']->isNotEmpty())
            <div class="border-t border-gray-100 pt-5">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Recent extractions</p>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 text-gray-500 text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium">When</th>
                                <th class="px-3 py-2 font-medium">By</th>
                                <th class="px-3 py-2 font-medium">Input</th>
                                <th class="px-3 py-2 font-medium">Result</th>
                                <th class="px-3 py-2 font-medium text-right">Orders</th>
                                <th class="px-3 py-2 font-medium">Model</th>
                                <th class="px-3 py-2 font-medium text-right">Cost</th>
                                <th class="px-3 py-2 font-medium text-right">Time</th>
                                <th class="px-3 py-2 font-medium">Placed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($stats['recent'] as $log)
                                <tr class="align-top">
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $log->user?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $log->source_type }}</td>
                                    <td class="px-3 py-2">
                                        <span @class([
                                            'px-1.5 py-0.5 rounded font-medium',
                                            'bg-sky-50 text-sky-700' => $log->resolution === 'parser',
                                            'bg-violet-50 text-violet-700' => $log->resolution === 'ai',
                                            'bg-red-50 text-red-700' => $log->resolution === 'ai_failed',
                                            'bg-amber-50 text-amber-700' => $log->resolution === 'ai_skipped',
                                        ])>{{ str_replace('_', ' ', $log->resolution) }}</span>
                                        @if ($log->error) <p class="mt-1 text-[11px] text-red-500 max-w-64 truncate" title="{{ $log->error }}">{{ $log->error }}</p> @endif
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $log->orders_ready }}/{{ $log->orders_found }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-500">{{ $log->model ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $log->cost !== null ? '$' . number_format((float) $log->cost, 5) : '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $log->latency_ms ? number_format($log->latency_ms / 1000, 1) . 's' : '—' }}</td>
                                    <td class="px-3 py-2">
                                        @foreach ($log->order_ids ?? [] as $orderId)
                                            <a href="{{ route('admin.sales.orders.show', $orderId) }}" class="text-indigo-600 hover:underline">#{{ $orderId }}</a>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($canManage)
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">AI Order settings save separately.</p>
            <button type="button" wire:click="save" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm disabled:opacity-50">
                Save AI Order Settings
            </button>
        </div>
    @endif
</div>
