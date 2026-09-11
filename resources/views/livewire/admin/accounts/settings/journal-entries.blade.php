<div x-data x-init="$store.pageName = { name: 'Journal Entries', slug: 'accounts-journal-entries' }">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-lg font-semibold text-gray-800">Journal Entries (Advanced)</h1>
        <button wire:click="openCreateModal" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            + Manual Entry
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Entry</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Lines</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $entry->entry_number }}</p>
                                <p class="text-xs text-gray-400">{{ $entry->entry_date->format('d M Y') }} — {{ $entry->description }}</p>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                @foreach ($entry->lines as $line)
                                    <div>{{ $line->account->code }} — {{ $line->debit > 0 ? 'Dr ' . number_format($line->debit, 2) : 'Cr ' . number_format($line->credit, 2) }}</div>
                                @endforeach
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->status->badgeClass() }}">{{ $entry->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($entry->status->value === 'posted')
                                    <button wire:click="reverse({{ $entry->id }})" type="button" class="text-xs text-red-500 hover:text-red-700">Reverse</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No journal entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4">{{ $entries->links() }}</div>
    </div>

    @if ($createModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="$set('createModal', false)">
            <div class="bg-white rounded-2xl shadow-lg w-full max-w-xl p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Manual Journal Entry</h2>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Date</label>
                        <input type="date" wire:model="entryDate" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Description</label>
                        <input type="text" wire:model="description" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300">
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach ($lines as $index => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model="lines.{{ $index }}.account_id" class="flex-1 px-3 py-2 text-sm rounded-lg border border-gray-300">
                                <option value="">Account</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} — {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" wire:model="lines.{{ $index }}.debit" placeholder="Debit" class="w-28 px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <input type="number" step="0.01" wire:model="lines.{{ $index }}.credit" placeholder="Credit" class="w-28 px-3 py-2 text-sm rounded-lg border border-gray-300">
                            <button wire:click="removeLine({{ $index }})" type="button" class="text-gray-400 hover:text-red-500">✕</button>
                        </div>
                    @endforeach
                </div>

                <button wire:click="addLine" type="button" class="text-sm text-indigo-600 hover:text-indigo-700 mt-3">+ Add line</button>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('createModal', false)" type="button" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                    <button wire:click="save" type="button" class="px-4 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Post Entry</button>
                </div>
            </div>
        </div>
    @endif
</div>
