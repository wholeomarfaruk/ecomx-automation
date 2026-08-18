<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-800">Discount Rules</h2>
        <button type="button" wire:click="addDiscountRule"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Rule
        </button>
    </div>

    <div class="space-y-3">
        @forelse($discountRules as $i => $rule)
            <div class="rounded-lg border border-gray-200 px-3 py-2.5">
                <div class="flex items-center gap-2">
                    <select wire:model.live="discountRules.{{ $i }}.type"
                        class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @foreach(\App\Enums\Sales\DiscountRuleType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="removeDiscountRule({{ $i }})"
                        class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-3 mt-2.5">
                    @if(in_array($rule['type'], ['percentage', 'fixed', 'fixed_price']))
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Value</label>
                            <input wire:model="discountRules.{{ $i }}.value" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    @endif

                    @if($rule['type'] === 'buy_x_get_y')
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Buy Quantity</label>
                            <input wire:model="discountRules.{{ $i }}.buy_quantity" type="number" min="1"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Get Quantity</label>
                            <input wire:model="discountRules.{{ $i }}.get_quantity" type="number" min="1"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    @endif

                    @if($rule['type'] === 'percentage')
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">Max Discount Amount</label>
                            <input wire:model="discountRules.{{ $i }}.max_discount_amount" type="number" step="0.01" min="0" placeholder="No cap"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">No discount rules added yet.</p>
        @endforelse
    </div>
</div>
