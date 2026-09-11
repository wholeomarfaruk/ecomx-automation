<div x-data x-init="$store.pageName = { name: 'Transactions', slug: 'accounts-transactions' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">Transactions</h1>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openCreateModal('income')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">
                + টাকা পেলাম
            </button>
            <button wire:click="openCreateModal('expense')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-red-500 rounded-xl hover:bg-red-600 transition shadow-sm">
                − টাকা দিলাম
            </button>
            <button wire:click="openCreateModal('transfer')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-sm">
                Transfer
            </button>
            <button wire:click="openCreateModal('conversion')" type="button"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Currency Conversion
            </button>
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search entry # or description…"
                class="flex-1 min-w-[200px] px-3 py-2 text-sm rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            <select wire:model.live="filterType" class="text-sm rounded-lg border border-gray-300 px-3 py-2">
                <option value="">All Types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterStatus" class="text-sm rounded-lg border border-gray-300 px-3 py-2">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Entry</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Lines</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Amount</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $entry->entry_number }}</p>
                                <p class="text-xs text-gray-400">{{ $entry->description }}</p>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $entry->entry_date->format('d M Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->transaction_type->badgeClass() }}">
                                    {{ $entry->transaction_type->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @foreach ($entry->lines as $line)
                                    <div>{{ $line->account->code }} {{ $line->account->name }} —
                                        @if ($line->debit > 0) Dr {{ number_format($line->debit, 2) }}
                                        @else Cr {{ number_format($line->credit, 2) }}
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-gray-800">{{ number_format($entry->totalDebit(), 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->status->badgeClass() }}">
                                    {{ $entry->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($entry->status->value === 'posted')
                                    <button wire:click="openVoidModal({{ $entry->id }})" type="button" class="text-xs text-red-500 hover:text-red-700">Void</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">No transactions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4">
            {{ $entries->links() }}
        </div>
    </div>

    {{-- Create Modal --}}
    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    @switch($mode)
                        @case('income') টাকা পেলাম (Income) @break
                        @case('expense') টাকা দিলাম (Expense) @break
                        @case('transfer') Transfer @break
                        @case('conversion') Currency Conversion @break
                    @endswitch
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="newDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($mode === 'income')
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">টাকা কোথায় জমা হলো (account)</label>
                            <select wire:model="newDebitAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newDebitAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">আয়ের ধরন (income account)</label>
                            <select wire:model="newCreditAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newCreditAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @elseif ($mode === 'expense')
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">খরচের ধরন (expense account)</label>
                            <select wire:model="newDebitAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newDebitAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">কোথা থেকে দিলাম (paid from)</label>
                            <select wire:model="newCreditAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newCreditAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">কোথা থেকে (from)</label>
                            <select wire:model="newCreditAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newCreditAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">কোথায় (to)</label>
                            <select wire:model="newDebitAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('newDebitAccountId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">{{ $mode === 'conversion' ? 'কত টাকা দিলাম' : 'পরিমাণ (amount)' }}</label>
                        <input type="number" step="0.01" wire:model="newAmount" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                        @error('newAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($mode === 'conversion')
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">কত পেলাম (received value, base currency)</label>
                            <input type="number" step="0.01" wire:model="newAmountReceived" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            @error('newAmountReceived') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if (in_array($mode, ['transfer', 'conversion']))
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Fee (optional)</label>
                                <input type="number" step="0.01" wire:model="newFee" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Fee account</label>
                                <select wire:model="newFeeAccountId" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                                    <option value="">—</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Note</label>
                        <input type="text" wire:model="newDescription" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="save" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Void confirm --}}
    @if ($voidTargetId)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('voidTargetId', null)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-sm p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Void this transaction?</h2>
                <p class="text-sm text-gray-500 mb-4">An offsetting entry will be posted; the original stays visible in history.</p>
                <input type="text" wire:model="voidReason" placeholder="Reason (optional)" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 mb-4">
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('voidTargetId', null)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="confirmVoid" type="button" class="px-4 py-2 text-sm text-white bg-red-600 hover:bg-red-700 rounded-lg">Void</button>
                </div>
            </div>
        </div>
    @endif
</div>
