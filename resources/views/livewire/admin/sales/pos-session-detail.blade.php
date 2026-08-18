<div x-data x-init="$store.pageName = { name: 'POS Session', slug: 'sales-pos-session-detail' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sales.pos.sessions') }}" wire:navigate
                class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:bg-gray-50 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $session ? 'Session #' . $session->id : 'Open New Session' }}</h1>
                @if($session)
                    <p class="text-xs text-gray-400">{{ $session->register->name }} · {{ $session->opened_at->format('d M, Y H:i') }}</p>
                @endif
            </div>
        </div>
        @if($session)
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $session->status->badgeClass() }}">
                {{ $session->status->label() }}
            </span>
        @endif
    </div>

    @if(! $session)
        {{-- Open Session Form --}}
        <div class="max-w-lg bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Open a New Session</h2>
            <form wire:submit.prevent="openSession" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Register <span class="text-red-500">*</span></label>
                    <select wire:model="registerId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select register —</option>
                        @foreach($registers as $register)
                            <option value="{{ $register->id }}">{{ $register->name }}</option>
                        @endforeach
                    </select>
                    @error('registerId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Opening Cash <span class="text-red-500">*</span></label>
                    <input wire:model="openingCash" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('openingCash') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                    <textarea wire:model="openNotes" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    Open Session
                </button>
            </form>
        </div>
    @else
        <div class="grid grid-cols-12 gap-6">

            {{-- Left --}}
            <div class="col-span-12 lg:col-span-8 space-y-6">

                {{-- Sales in this session --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Sales</h2>
                    <div class="space-y-2">
                        @forelse($session->sales as $sale)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5">
                                <div>
                                    <a href="{{ route('admin.sales.orders.show', $sale->order_id) }}" wire:navigate class="text-sm text-indigo-600 hover:underline">Order #{{ $sale->order_id }}</a>
                                    <span class="block text-xs text-gray-400">{{ $sale->customer?->full_name ?? 'Walk-in' }} · {{ $sale->created_at->format('H:i') }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ number_format($sale->order->total_amount ?? 0, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No sales recorded in this session yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Cash movements --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Cash Movements</h2>
                    <div class="space-y-2 mb-4">
                        @forelse($session->cashMovements as $movement)
                            <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5">
                                <div>
                                    <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $movement->type->value) }}</span>
                                    @if($movement->reason)
                                        <span class="block text-xs text-gray-400">{{ $movement->reason }}</span>
                                    @endif
                                </div>
                                <span class="text-sm font-medium {{ $movement->type->isInflow() ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ $movement->type->isInflow() ? '+' : '−' }}{{ number_format($movement->amount, 2) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No cash movements recorded yet.</p>
                        @endforelse
                    </div>

                    @if($session->status->value === 'open')
                        <form wire:submit.prevent="addCashMovement" class="flex items-end gap-3 pt-4 border-t border-gray-100">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Type</label>
                                <select wire:model="movementType" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <option value="cash_in">Cash In</option>
                                    <option value="cash_out">Cash Out</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Amount</label>
                                <input wire:model="movementAmount" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason</label>
                                <input wire:model="movementReason" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition shrink-0">
                                Add
                            </button>
                        </form>
                        @error('movementAmount') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                    @endif
                </div>
            </div>

            {{-- Right --}}
            <div class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Session Summary</h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Cashier</span>
                            <span class="text-gray-800 font-medium">{{ $session->user->name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Branch</span>
                            <span class="text-gray-800">{{ $session->register->branch->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Opening Cash</span>
                            <span class="text-gray-800">{{ number_format($session->opening_cash, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                            <span class="text-gray-500">Expected Cash</span>
                            <span class="text-indigo-600 font-semibold">{{ number_format($expectedCash, 2) }}</span>
                        </div>
                        @if($session->status->value === 'closed')
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Closing Cash</span>
                                <span class="text-gray-800">{{ number_format($session->closing_cash, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Variance</span>
                                @php($closeVariance = (float) $session->closing_cash - $expectedCash)
                                <span class="{{ $closeVariance == 0 ? 'text-gray-500' : ($closeVariance > 0 ? 'text-emerald-600' : 'text-red-500') }} font-medium">
                                    {{ $closeVariance > 0 ? '+' : '' }}{{ number_format($closeVariance, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    @if($session->status->value === 'open')
                        <button wire:click="openCloseModal" type="button"
                            class="w-full mt-5 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition">
                            Close Session
                        </button>
                        <a href="{{ route('admin.sales.pos.screen') }}" wire:navigate
                            class="block text-center w-full mt-2 px-4 py-2.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            Go to POS Screen
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Close Session Modal --}}
        <div x-cloak x-data="{ open: @entangle('closeModal') }" x-show="open" x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Close Session</h2>
                    </div>
                    <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit.prevent="closeSession" class="px-6 py-5 space-y-4">
                    <p class="text-sm text-gray-500">Expected cash in drawer: <span class="font-semibold text-gray-800">{{ number_format($expectedCash, 2) }}</span></p>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Actual Closing Cash <span class="text-red-500">*</span></label>
                        <input wire:model.live="closingCash" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('closingCash') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($variance !== null)
                        <div class="rounded-lg px-3 py-2.5 {{ $variance == 0 ? 'bg-gray-50' : ($variance > 0 ? 'bg-emerald-50' : 'bg-red-50') }}">
                            <span class="text-xs {{ $variance == 0 ? 'text-gray-600' : ($variance > 0 ? 'text-emerald-600' : 'text-red-500') }}">
                                Variance: {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                                {{ $variance == 0 ? '(balanced)' : ($variance > 0 ? '(over)' : '(short)') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition">Close Session</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
