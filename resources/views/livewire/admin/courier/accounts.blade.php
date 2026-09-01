<div x-data x-init="$store.pageName = { name: 'Courier', slug: 'courier' }" class="space-y-6">

    @include('livewire.admin.courier.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Courier Accounts</h2>
                <p class="text-xs text-gray-400">Connect one or more accounts per courier — mark one as default per courier for auto-selection</p>
            </div>
            @can('courier_configuration.manage')
                <button wire:click="openCreate" type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">
                    + Add Account
                </button>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-medium text-gray-400">
                        <th class="px-6 py-3">Courier</th>
                        <th class="px-6 py-3">Account Name</th>
                        <th class="px-6 py-3">Balance</th>
                        <th class="px-6 py-3">Last Tested</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($accounts as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $account->courier->name }}</td>
                            <td class="px-6 py-3 text-gray-600">
                                {{ $account->name }}
                                @if ($account->is_default)
                                    <span class="ml-1.5 inline-flex text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600">Default</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 font-mono text-xs text-gray-500">{{ $account->last_balance ?? '—' }}</td>
                            <td class="px-6 py-3 text-xs text-gray-400">{{ $account->last_tested_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $account->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button wire:click="testAccount({{ $account->id }})" type="button" wire:loading.attr="disabled"
                                        class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Test</button>
                                    <button wire:click="checkBalance({{ $account->id }})" type="button" wire:loading.attr="disabled"
                                        class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Balance</button>
                                    @can('courier_configuration.manage')
                                        <button wire:click="edit({{ $account->id }})" type="button"
                                            class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Edit</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">No accounts connected yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Add/Edit Account Slide-over ── --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 px-4" wire:click.self="cancelForm">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">{{ $editingAccountId ? 'Edit Account' : 'Add Account' }}</h3>
                    <button wire:click="cancelForm" type="button" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form wire:submit.prevent="save" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Courier</label>
                        <select wire:model.live="selectedCourierKey" @if($editingAccountId) disabled @endif
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50">
                            <option value="">Select a courier</option>
                            @foreach ($couriers as $courier)
                                <option value="{{ $courier->driver_key }}">{{ $courier->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedCourierKey') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Account Name</label>
                        <input wire:model="name" type="text" placeholder="e.g. Main Account" autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($selectedMeta)
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Credentials</p>
                            <div class="space-y-3">
                                @foreach ($selectedMeta['fields'] as $field)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $field['label'] }}</label>
                                        <input wire:model="credentials.{{ $field['key'] }}"
                                            type="{{ $field['type'] === 'password' ? 'password' : 'text' }}"
                                            autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-gray-100 pt-4 flex items-center gap-6">
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input wire:model="is_default" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Set as default
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input wire:model="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Active
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button wire:click="cancelForm" type="button" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">Save Account</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
