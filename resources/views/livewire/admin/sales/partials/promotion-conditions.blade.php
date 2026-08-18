<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-gray-800">Conditions</h2>
        <button type="button" wire:click="addCondition"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Condition
        </button>
    </div>

    <div class="space-y-3">
        @forelse($conditions as $i => $condition)
            <div class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5">
                <select wire:model="conditions.{{ $i }}.type"
                    class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @foreach(\App\Enums\Sales\ConditionType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <select wire:model="conditions.{{ $i }}.operator"
                    class="w-40 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @foreach(\App\Enums\Sales\ConditionOperator::cases() as $operator)
                        <option value="{{ $operator->value }}">{{ $operator->label() }}</option>
                    @endforeach
                </select>
                <input wire:model="conditions.{{ $i }}.value" type="text" placeholder="Value (comma-separate for In/Not In)"
                    class="flex-1 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <button type="button" wire:click="removeCondition({{ $i }})"
                    class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        @empty
            <p class="text-sm text-gray-400">No conditions added. Without conditions this promotion applies unconditionally.</p>
        @endforelse
    </div>
</div>
